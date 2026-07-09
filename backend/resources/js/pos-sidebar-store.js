// pos-sidebar-store.js
//
// Single source of truth for the four contextual buttons of the POS sidebar
// (Asignar cliente, Ingresar monto recibido, Convertir a fiado, Cambiar método).
// Replaces the inline `x-data="{...}"` that used to live in
// backend/resources/views/pos/index.blade.php.
//
// Persistence: in-memory only. No $persist, no localStorage. Reloading the
// page resets the store to the server-provided initial state. See
// pos-contextual-buttons-state spec (Q1: no PII leakage on shared POS).
//
// The vanilla DOMContentLoaded script in the Blade (search, line items,
// refreshSummary) is NOT migrated to Alpine. The store writes hidden inputs
// via syncToHiddenInputs(); the vanilla script reads those hidden inputs.
export function registerPosSidebarStore(Alpine, initial) {
  const safeInitial = initial ?? {};

  Alpine.store('posSidebar', {
    // --- State ---
    activePanel: null,
    pinnedPanels: [],
    usedPanels: [],
    creditActive: Boolean(safeInitial.creditActive),
    paymentMethod: safeInitial.paymentMethod ?? 'cash',
    selectedCustomerId: safeInitial.selectedCustomerId ?? null,
    selectedCustomerName: safeInitial.selectedCustomerName ?? '',
    fiadoAutoEnabled: Boolean(safeInitial.fiadoAutoEnabled),
    customerQuery: safeInitial.selectedCustomerName ?? '',
    customerResults: [],
    customerLoading: false,
    customerHighlightIndex: -1,

    // --- Actions ---

    togglePanel(name) {
      if (this.activePanel === name) {
        if (!this.pinnedPanels.includes(name)) {
          this.activePanel = null;
        }
      } else {
        this.activePanel = name;
      }
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
      const paymentInput = document.getElementById('pos-payment-method');
      const creditInput = document.getElementById('pos-allow-credit-sale');

      if (paymentInput) {
        paymentInput.value = this.paymentMethod;
      }

      if (creditInput) {
        creditInput.value = this.creditActive ? '1' : '0';
      }
    },

    handleCreditToggle() {
      if (this.creditActive) {
        this.creditActive = false;
        this.activePanel = this.activePanel === 'credit' ? null : this.activePanel;
        this.syncToHiddenInputs();
        return;
      }

      if (!this.fiadoAutoEnabled) {
        this.activePanel = 'credit';
        alert('El fiado automático está desactivado. Actívalo en configuración.');
        return;
      }

      const inlineError = document.getElementById('pos-customer-inline-error');
      if (!this.selectedCustomerId) {
        if (inlineError) {
          inlineError.textContent = 'Debes seleccionar un cliente para registrar fiado desde POS.';
          inlineError.classList.remove('hidden');
        }
        this.activePanel = 'customer';
        return;
      }

      this.creditActive = true;
      this.activePanel = 'credit';
      this.syncToHiddenInputs();
    },

    async searchCustomers() {
      const query = (this.customerQuery ?? '').trim();

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
            'Accept': 'application/json',
          },
        });

        if (!response.ok) {
          this.customerResults = [];
          this.customerHighlightIndex = -1;
          return;
        }

        const data = await response.json();
        this.customerResults = Array.isArray(data.results) ? data.results : [];
        this.customerHighlightIndex = -1;
      } catch (e) {
        this.customerResults = [];
        this.customerHighlightIndex = -1;
      } finally {
        this.customerLoading = false;
      }
    },

    selectCustomer(customer) {
      if (!customer) {
        return;
      }

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

      const inlineError = document.getElementById('pos-customer-inline-error');
      if (inlineError) {
        inlineError.classList.add('hidden');
        inlineError.textContent = '';
      }

      this.syncToHiddenInputs();
    },

    // --- Getters ---

    isPanelVisible(name) {
      return this.activePanel === name || this.pinnedPanels.includes(name);
    },

    isButtonActive(name) {
      if (name === 'credit') {
        return this.creditActive;
      }

      return this.isPanelVisible(name);
    },

    // Stub in PR 1. PR 2 (pos-panel-reactivation) replaces this with a real
    // implementation that reads `usedPanels`.
    isButtonUsed(name) {
      return false;
    },
  });
}
