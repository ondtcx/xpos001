// pos-store.js
//
// New Alpine store for the POS v2 2-column layout. Owns the reactive state
// of the entire point-of-sale: cart, customer, payment, toast. Submission
// becomes AJAX (POST to /pos with Accept: application/json) and the
// controller returns JSON. Replaces the legacy `posSidebar` store which is
// removed in PR 3.

export function registerPosStore(Alpine, initial = {}) {
  const init = initial || {};
  const productos = Array.isArray(init.productos) ? init.productos : [];
  const clientes = Array.isArray(init.clientes) ? init.clientes : [];
  const defaultCliente = clientes.find((c) => c.is_default) ?? clientes[0] ?? null;
  let clienteSearchTimer = null;

  Alpine.store('posStore', {
    // --- State ---
    items: [],
    clientes,
    productos,
    cliente: defaultCliente
      ? {
          id: defaultCliente.id,
          nombre: defaultCliente.name,
          documento: defaultCliente.document ?? null,
          saldo_fiado: Number(defaultCliente.saldo_fiado ?? 0),
        }
      : { id: null, nombre: '', documento: null, saldo_fiado: 0 },
    generalId: defaultCliente?.id ?? null,
    metodo: 'efectivo',
    recibido: '',
    aviso: null,
    procesando: false,
    busqueda: '',
    clienteOpen: false,
    clienteQuery: '',
    clienteHighlight: -1,
    clienteResults: [],
    clienteSearching: false,

    // --- Getters (Alpine evaluates these as functions on the store) ---
    get subtotal() {
      return this.items.reduce((sum, item) => sum + Number(item.precio) * Number(item.cantidad), 0);
    },
    get total() {
      return this.subtotal;
    },
    get itemsCount() {
      return this.items.reduce((sum, item) => sum + Number(item.cantidad), 0);
    },
    get vuelto() {
      const recibidoNum = parseFloat(this.recibido || 0);
      return Math.max(0, recibidoNum - this.total);
    },
    get puedeCobrar() {
      if (this.itemsCount <= 0) return false;
      if (this.metodo === 'fiado' && this.cliente && this.cliente.id === this.generalId) return false;
      return true;
    },
    get filteredProductos() {
      const q = (this.busqueda || '').trim().toLowerCase();
      if (q === '') return this.productos;
      return this.productos.filter((p) => {
        const haystack = [p.nombre, p.codigo, p.barcode, p.categoria]
          .filter(Boolean)
          .join(' ')
          .toLowerCase();
        return haystack.includes(q);
      });
    },
    get filteredClientes() {
      const q = (this.clienteQuery || '').trim();
      if (q === '') return this.clientes;
      return this.clienteResults;
    },

    // --- Actions ---
    agregar(producto) {
      if (!producto || Number(producto.disponibles) <= 0) return;
      const existing = this.items.find((it) => it.id === producto.id);
      if (existing) {
        existing.cantidad = Number(existing.cantidad) + 1;
        return;
      }
      this.items.push({
        id: producto.id,
        nombre: producto.nombre,
        precio: Number(producto.precio),
        disponibles: Number(producto.disponibles),
        lote: producto.lote ?? null,
        vence: producto.vence ?? null,
        categoria: producto.categoria ?? null,
        cantidad: 1,
      });
    },
    cambiarCantidad(id, qty) {
      const line = this.items.find((it) => it.id === id);
      if (!line) return;
      const next = Math.max(1, Math.floor(Number(qty) || 1));
      line.cantidad = next;
    },
    quitar(id) {
      this.items = this.items.filter((it) => it.id !== id);
    },
    limpiar() {
      this.items = [];
      this.recibido = '';
    },
    anularVenta() {
      this.items = [];
      this.recibido = '';
      this.metodo = 'efectivo';
      const def = this.clientes.find((c) => c.is_default) ?? this.clientes[0] ?? null;
      this.cliente = def
        ? {
            id: def.id,
            nombre: def.name,
            documento: def.document ?? null,
            saldo_fiado: Number(def.saldo_fiado ?? 0),
          }
        : { id: null, nombre: '', documento: null, saldo_fiado: 0 };
    },
    setCliente(customer) {
      if (!customer) return;
      this.cliente = {
        id: customer.id,
        nombre: customer.name,
        documento: customer.document ?? null,
        saldo_fiado: Number(customer.saldo_fiado ?? 0),
      };
      this.clienteOpen = false;
      this.clienteHighlight = -1;
      this.clienteQuery = '';
      this.clienteResults = [];
      // If Fiado is active and we just picked Cliente General, fall back to efectivo.
      if (this.metodo === 'fiado' && customer.id === this.generalId) {
        this.metodo = 'efectivo';
      }
    },
    setMetodo(metodo) {
      if (metodo === 'fiado' && this.cliente && this.cliente.id === this.generalId) {
        return;
      }
      this.metodo = metodo;
    },
    toggleCliente() {
      this.clienteOpen = !this.clienteOpen;
      this.clienteHighlight = -1;
      if (!this.clienteOpen) {
        this.clienteQuery = '';
        this.clienteResults = [];
        clearTimeout(clienteSearchTimer);
        this.clienteSearching = false;
      }
    },
    closeCliente() {
      this.clienteOpen = false;
      this.clienteHighlight = -1;
      this.clienteQuery = '';
      this.clienteResults = [];
      clearTimeout(clienteSearchTimer);
      this.clienteSearching = false;
    },
    searchCliente(query) {
      this.clienteQuery = query;
      this.clienteHighlight = -1;
      clearTimeout(clienteSearchTimer);

      const q = (query || '').trim();
      if (q === '') {
        this.clienteResults = [];
        this.clienteSearching = false;
        return;
      }

      this.clienteSearching = true;
      clienteSearchTimer = setTimeout(async () => {
        try {
          const response = await fetch(`/pos/customers/search?q=${encodeURIComponent(q)}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          });
          const data = await response.json();
          this.clienteResults = Array.isArray(data.results) ? data.results : [];
        } catch (e) {
          this.clienteResults = [];
        } finally {
          this.clienteSearching = false;
        }
      }, 250);
    },
    setRecibido(value) {
      this.recibido = String(value ?? '');
    },
    quickAmount(value) {
      this.setRecibido(String(value));
    },
    cerrarAviso() {
      this.aviso = null;
    },
    moveClienteHighlight(delta) {
      const list = this.filteredClientes;
      if (list.length === 0) {
        this.clienteHighlight = -1;
        return;
      }
      const next = this.clienteHighlight + delta;
      if (next < 0) this.clienteHighlight = 0;
      else if (next >= list.length) this.clienteHighlight = list.length - 1;
      else this.clienteHighlight = next;
    },
    async cobrar() {
      if (!this.puedeCobrar || this.procesando) return;
      this.procesando = true;

      const payload = {
        metodo: this.metodo,
        customer_id: this.cliente?.id ?? null,
        items: this.items.map((it) => ({
          sale_presentation_id: it.id,
          quantity: it.cantidad,
        })),
        received_amount: this.metodo === 'efectivo' ? this.recibido : null,
        allow_credit_sale: this.metodo === 'fiado' ? 1 : 0,
        confirm_credit_sale: this.metodo === 'fiado' ? 1 : 0,
        payment_method: this.metodo === 'transfer' ? 'transfer' : 'cash',
      };

      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

      try {
        const response = await fetch('/pos', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
          },
          body: JSON.stringify(payload),
        });

        if (response.ok) {
          const data = await response.json();
          this.aviso = {
            tipo: 'success',
            mensaje: data?.message ?? 'Venta cobrada.',
          };
          this.anularVenta();
          setTimeout(() => this.cerrarAviso(), 3500);
          return;
        }

        let errorMessage = 'No se pudo cobrar la venta.';
        try {
          const data = await response.json();
          if (data?.errors) {
            const firstKey = Object.keys(data.errors)[0];
            errorMessage = data.errors[firstKey]?.[0] ?? errorMessage;
          } else if (data?.message) {
            errorMessage = data.message;
          }
        } catch (_) {
          // body wasn't JSON
        }
        this.aviso = { tipo: 'error', mensaje: errorMessage };
      } catch (e) {
        this.aviso = { tipo: 'error', mensaje: 'Error de red al cobrar la venta.' };
      } finally {
        this.procesando = false;
      }
    },

    // --- Currency formatter (es-AR style but using USD prefix per locked decision #1/#13) ---
    formatMoney(value) {
      const num = Number(value);
      const sign = num < 0 ? '-' : '';
      const abs = Math.abs(num);
      const fixed = abs.toFixed(2);
      const [intPart, decPart] = fixed.split('.');
      const withThousands = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      return `${sign}USD ${withThousands}.${decPart}`;
    },
  });
}
