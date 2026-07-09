// pos-sidebar-store.js
//
// Foundation Alpine store for the POS sidebar. Owns the reactive state of the
// 4 contextual buttons (Asignar cliente, Ingresar monto recibido, Convertir a
// fiado, Cambiar método) and the 4 associated panels. PR 1a introduced the
// store and migrated the Asignar cliente + Cambiar método buttons (plus the
// customer typeahead methods that the customer panel depends on). PR 1b
// migrated the Ingresar monto recibido + Convertir a fiado buttons and added
// the `receivedAmount` reactive state. PR 2 added real `usedPanels` tracking
// (markUsed action + isButtonUsed getter that reads usedPanels) and a `used`
// class binding on the 4 buttons; togglePanel now calls markUsed on the
// open branch. PR 3 verified the typeahead wiring (Blade input binding,
// dropdown, keyboard nav, @click.outside) and added snapshot tests for
// the no-quick-create invariant; the store methods (searchCustomers,
// selectCustomer, clearCustomer) shipped with PR 1a's scope creep and
// remained unchanged in PR 3.

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
      this.markUsed(name);
      this.syncToHiddenInputs();
    },

    // Records that `name` has been used at least once this session. Idempotent:
    // repeated calls do not duplicate entries. Drives the `used` class binding
    // (see isButtonUsed) so a button keeps a visual hint after its panel closes.
    markUsed(name) {
      if (!this.usedPanels.includes(name)) {
        this.usedPanels.push(name);
      }
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

    // PR 2: real implementation reads usedPanels (idempotent push from markUsed).
    isButtonUsed(name) {
      return this.usedPanels.includes(name);
    },
  });
}
