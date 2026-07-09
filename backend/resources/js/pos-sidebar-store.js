// pos-sidebar-store.js
//
// Foundation Alpine store for the POS sidebar. Owns the reactive state of the
// 4 contextual buttons (Asignar cliente, Ingresar monto recibido, Convertir a
// fiado, Cambiar método) and the 4 associated panels. PR 1a introduces the
// store and migrates the Asignar cliente + Cambiar método buttons; PR 1b will
// migrate the remaining two. PR 2 will add `usedPanels` tracking; PR 3 will
// refine the typeahead. Until then `isButtonUsed` is a stub returning `false`.

export function registerPosSidebarStore(Alpine, initial = {}) {
  const init = initial || {};

  Alpine.store('posSidebar', {
    // --- State ---
    activePanel: null,
    pinnedPanels: [],
    usedPanels: [],
    creditActive: Boolean(init.creditActive),
    paymentMethod: init.paymentMethod || 'cash',
    selectedCustomerId: init.selectedCustomerId ?? null,
    selectedCustomerName: init.selectedCustomerName || '',
    fiadoAutoEnabled: init.fiadoAutoEnabled !== false,
    receivedAmount: init.receivedAmount || '',
    customerQuery: init.customerQuery || '',
    customerResults: [],
    customerLoading: false,
    customerHighlightIndex: -1,

    // --- Actions ---
    togglePanel(name) {
      if (this.activePanel === name) {
        if (!this.pinnedPanels.includes(name)) {
          this.activePanel = null;
        }
        return;
      }

      this.activePanel = name;
      this.syncToHiddenInputs();
    },

    togglePin(name) {
      const index = this.pinnedPanels.indexOf(name);
      if (index === -1) {
        this.pinnedPanels.push(name);
      } else {
        this.pinnedPanels.splice(index, 1);
      }
    },

    syncToHiddenInputs() {
      const payment = document.getElementById('pos-payment-method');
      const credit = document.getElementById('pos-allow-credit-sale');
      if (payment) payment.value = this.paymentMethod;
      if (credit) credit.value = this.creditActive ? '1' : '0';
    },

    handleCreditToggle() {
      if (this.creditActive) {
        this.creditActive = false;
        if (this.activePanel === 'credit') this.activePanel = null;
        this.syncToHiddenInputs();
        return;
      }

      if (!this.fiadoAutoEnabled) {
        this.activePanel = 'credit';
        window.alert('El fiado automático está desactivado. Actívalo en configuración.');
        return;
      }

      if (!this.selectedCustomerId) {
        const err = document.getElementById('pos-customer-inline-error');
        if (err) {
          err.textContent = 'Debes seleccionar un cliente para registrar fiado desde POS.';
          err.classList.remove('hidden');
        }
        this.activePanel = 'customer';
        return;
      }

      this.creditActive = true;
      this.activePanel = 'credit';
      this.syncToHiddenInputs();
    },

    async searchCustomers() {
      const query = (this.customerQuery || '').trim();
      if (query.length < 1) {
        this.customerResults = [];
        this.customerHighlightIndex = -1;
        return;
      }

      this.customerLoading = true;

      try {
        const response = await fetch(`/pos/customers/search?q=${encodeURIComponent(query)}`, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
          },
        });

        if (!response.ok) {
          this.customerResults = [];
          return;
        }

        const data = await response.json();
        this.customerResults = Array.isArray(data.results) ? data.results : [];
        this.customerHighlightIndex = -1;
      } catch (e) {
        this.customerResults = [];
      } finally {
        this.customerLoading = false;
      }
    },

    selectCustomer(customer) {
      this.selectedCustomerId = customer.id;
      this.selectedCustomerName = customer.name;
      this.customerQuery = customer.name;
      this.customerResults = [];
      this.customerHighlightIndex = -1;
      this.syncToHiddenInputs();
    },

    clearCustomer() {
      this.selectedCustomerId = null;
      this.selectedCustomerName = '';
      this.customerQuery = '';
      this.customerResults = [];
      this.customerHighlightIndex = -1;
      const err = document.getElementById('pos-customer-inline-error');
      if (err) err.classList.add('hidden');
      this.syncToHiddenInputs();
    },

    // --- Getters ---
    isPanelVisible(name) {
      return this.activePanel === name || this.pinnedPanels.includes(name);
    },

    isButtonActive(name) {
      if (name === 'credit') return this.creditActive;
      return this.activePanel === name || this.pinnedPanels.includes(name);
    },

    // PR 1: stub returns false. PR 2 will replace with `return this.usedPanels.includes(name)`.
    isButtonUsed(name) {
      return false;
    },
  });
}
