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

  // 🔹 nuevo: estado de orden
  order: 'asc' | 'desc' = 'asc';

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

      if (this.startAt && this.endAt) {
        const diffMs = new Date(this.endAt).getTime() - new Date(this.startAt).getTime();
        this.dias = Math.max(1, Math.ceil(diffMs / (1000 * 60 * 60 * 24)));
      }

      this.cargarResultados();
    });
  }

  private cargarResultados() {
    this.cargando = true;

    // 🔹 Si hay parámetros → disponibilidad real por sucursal y fechas
    if (this.startAt && this.endAt && this.pickupLocationId) {
      console.log('[Resultados] Fetch /vehicles/available', {
        pickupLocationId: this.pickupLocationId,
        startAt: this.startAt,
        endAt: this.endAt
      });

      this.cotizarService.getAvailableVehicles({
        pickupLocationId: this.pickupLocationId,
        startAt: this.startAt,
        endAt: this.endAt
      }).subscribe({
        next: (data) => {
          console.log('✅ Disponibles:', data);
          this.resultados = data;
          this.ordenarPorPrecio(this.order);   // 👈 aplicar orden
          this.cargando = false;

          // Fallback amable si no hay disponibles
          if (this.resultados.length === 0) {
            console.warn('No hay disponibles; mostrando catálogo general como fallback.');
            this.cargando = true;
            this.cotizarService.buscarVehiculos().subscribe({
              next: (all) => { this.resultados = all; this.ordenarPorPrecio(this.order); this.cargando = false; },
              error: () => { this.resultados = []; this.cargando = false; }
            });
          }
        },
        error: (err) => {
          console.error('❌ Error disponibilidad', err);
          // Ante error (400/422/etc), mostramos catálogo general
          this.cotizarService.buscarVehiculos().subscribe({
            next: (all) => { this.resultados = all; this.ordenarPorPrecio(this.order); this.cargando = false; },
            error: () => { this.resultados = []; this.cargando = false; }
          });
        }
      });

    } else {
      // 🔹 Sin parámetros → catálogo general
      console.log('[Resultados] Fetch catálogo general /vehicles');
      this.cotizarService.buscarVehiculos().subscribe({
        next: (data) => { console.log('🚗 Vehículos (sin filtro):', data); this.resultados = data; this.ordenarPorPrecio(this.order); this.cargando = false; },
        error: () => { this.resultados = []; this.cargando = false; }
      });
    }
  }

  // 🔹 nuevo: ordenar por precio
  ordenarPorPrecio(dir: 'asc' | 'desc') {
    this.order = dir;
    this.resultados = [...this.resultados].sort((a, b) => {
      const da = a.dailyRate ?? 0;
      const db = b.dailyRate ?? 0;
      return dir === 'asc' ? da - db : db - da;
    });
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
