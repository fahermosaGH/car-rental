import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { CotizarService } from '../../services/cotizar.service';
import { VehicleOption } from '../../models/quote';

@Component({
  selector: 'app-resultados',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './resultados.component.html',
  styleUrls: ['./resultados.component.css']
})
export class ResultadosComponent implements OnInit {
  resultados: VehicleOption[] = [];
  dias = 1;
  startAt: string = '';
  endAt: string = '';
  pickupLocationId = 1;
  dropoffLocationId = 1;

  cargando = true;

  // Orden por precio
  order: 'asc' | 'desc' = 'asc';

  // Filtro por categoría (lista visible en la UI)
  availableCategories = ['Todos', 'Económico', 'Compacto', 'SUV', 'Camioneta', 'Sedán', 'Premium', 'Largos'];
  selectedCategory: string = 'Todos';

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cotizarService: CotizarService
  ) {}

  ngOnInit() {
    this.route.queryParamMap.subscribe(params => {
      this.startAt = params.get('startAt') || '';
      this.endAt = params.get('endAt') || '';
      this.pickupLocationId = +(params.get('pickupLocationId') || 1);
      this.dropoffLocationId = +(params.get('dropoffLocationId') || 1);

      const cat = params.get('category');
      if (cat && cat.trim() !== '') {
        // si viene una categoría desconocida, igual la respetamos para no “pisar” la URL
        this.selectedCategory = this.availableCategories.includes(cat) ? cat : cat;
      } else {
        this.selectedCategory = 'Todos';
      }

      if (this.startAt && this.endAt) {
        const diffMs = new Date(this.endAt).getTime() - new Date(this.startAt).getTime();
        this.dias = Math.max(1, Math.ceil(diffMs / (1000 * 60 * 60 * 24)));
      }

      this.cargarResultados();
    });
  }

  private cargarResultados() {
    this.cargando = true;

    // Si hay parámetros → disponibilidad real por sucursal y fechas
    if (this.startAt && this.endAt && this.pickupLocationId) {
      const category = this.selectedCategory && this.selectedCategory !== 'Todos'
        ? this.selectedCategory
        : undefined;

      console.log('[Resultados] Fetch /vehicles/available', {
        pickupLocationId: this.pickupLocationId,
        startAt: this.startAt,
        endAt: this.endAt,
        category
      });

      this.cotizarService.getAvailableVehicles({
        pickupLocationId: this.pickupLocationId,
        startAt: this.startAt,
        endAt: this.endAt,
        category
      }).subscribe({
        next: (data) => {
          this.resultados = data;
          this.aplicarFiltrosLocalesSiHaceFalta(); // mantiene orden
          this.cargando = false;

          // Fallback si no hay disponibles
          if (this.resultados.length === 0) {
            console.warn('No hay disponibles; mostrando catálogo general como fallback.');
            this.cargando = true;
            this.cotizarService.buscarVehiculos().subscribe({
              next: (all) => {
                this.resultados = this.aplicarFiltroCategoriaLocal(all);
                this.ordenarPorPrecio(this.order);
                this.cargando = false;
              },
              error: () => { this.resultados = []; this.cargando = false; }
            });
          }
        },
        error: (err) => {
          console.error('❌ Error disponibilidad', err);
          // Ante error (400/422/etc), mostramos catálogo general
          this.cotizarService.buscarVehiculos().subscribe({
            next: (all) => {
              this.resultados = this.aplicarFiltroCategoriaLocal(all);
              this.ordenarPorPrecio(this.order);
              this.cargando = false;
            },
            error: () => { this.resultados = []; this.cargando = false; }
          });
        }
      });

    } else {
      // Sin parámetros → catálogo general
      console.log('[Resultados] Fetch catálogo general /vehicles');
      this.cotizarService.buscarVehiculos().subscribe({
        next: (data) => {
          this.resultados = this.aplicarFiltroCategoriaLocal(data);
          this.ordenarPorPrecio(this.order);
          this.cargando = false;
        },
        error: () => { this.resultados = []; this.cargando = false; }
      });
    }
  }

  // Ordenar por precio
  ordenarPorPrecio(dir: 'asc' | 'desc') {
    this.order = dir;
    this.resultados = [...this.resultados].sort((a, b) => {
      const da = a.dailyRate ?? 0;
      const db = b.dailyRate ?? 0;
      return dir === 'asc' ? da - db : db - da;
    });
  }

  // Cambiar categoría desde la UI
  cambiarCategoria(cat: string) {
    this.selectedCategory = cat;
    // Actualiza URL (merge) y recarga resultados con el nuevo filtro
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { category: cat !== 'Todos' ? cat : null },
      queryParamsHandling: 'merge'
    });
    // La suscripción a queryParamMap gatilla cargarResultados()
  }

  // Filtro local por categoría (para fallback / catálogo general)
  private aplicarFiltroCategoriaLocal(items: VehicleOption[]): VehicleOption[] {
    if (!this.selectedCategory || this.selectedCategory === 'Todos') return items;
    return items.filter(v => (v.category || '').toLowerCase() === this.selectedCategory.toLowerCase());
  }

  private aplicarFiltrosLocalesSiHaceFalta() {
    // En /available ya viene filtrado por categoría si la enviamos.
    // Solo mantenemos el orden local.
    this.ordenarPorPrecio(this.order);
  }

  calcularTotal(rate: number) {
    return rate * this.dias;
  }

  verDetalle(id: number) {
    if (!this.startAt || !this.endAt) {
      alert('⚠️ Faltan las fechas de búsqueda. Volvé al buscador.');
      return;
    }

    this.router.navigate(['/cotizar/detalle', id], {
      queryParams: {
        dias: this.dias,
        startAt: this.startAt,
        endAt: this.endAt,
        pickupLocationId: this.pickupLocationId,
        dropoffLocationId: this.dropoffLocationId
      }
    });
  }
}

