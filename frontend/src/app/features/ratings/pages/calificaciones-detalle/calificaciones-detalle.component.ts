import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';

import { CotizarService } from '../../../cotizar/services/cotizar.service';
import { VehicleOption } from '../../../cotizar/models/quote';

type RatingItemUI = {
  rating: number | null;
  comment: string | null;
  date: string | null;
};

@Component({
  selector: 'app-calificaciones-detalle',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './calificaciones-detalle.component.html',
  styleUrls: ['./calificaciones-detalle.component.css'],
})
export class CalificacionesDetalleComponent implements OnInit {
  loading = true;
  error = '';

  vehicleId = 0;
  vehiculo?: VehicleOption;

  ratingAvg: number | null = null;
  ratingCount = 0;
  items: RatingItemUI[] = [];

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cotizarService: CotizarService
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.router.navigate(['/calificaciones']);
      return;
    }

    this.vehicleId = id;
    this.load();
  }

  load(): void {
    this.loading = true;
    this.error = '';

    this.cotizarService.obtenerVehiculoPorId(this.vehicleId).subscribe({
      next: (v) => {
        if (!v) {
          this.loading = false;
          this.error = 'Vehículo no encontrado.';
          return;
        }
        this.vehiculo = v;

        this.cotizarService.getVehicleRatings(this.vehicleId, 12).subscribe({
          next: (r: any) => {
            this.ratingAvg = r?.ratingAvg ?? null;
            this.ratingCount = r?.ratingCount ?? 0;

            const rawItems = Array.isArray(r?.items) ? r.items : [];
            this.items = rawItems.map((it: any) => ({
              rating: typeof it?.rating === 'number' ? it.rating : null,
              comment: it?.comment ?? it?.ratingComment ?? null,
              date: it?.endAt ?? it?.date ?? null,
            }));

            this.loading = false;
          },
          error: () => {
            this.loading = false;
            this.error = 'No se pudieron cargar las reseñas del vehículo.';
          },
        });
      },
      error: () => {
        this.loading = false;
        this.error = 'No se pudo cargar el vehículo.';
      },
    });
  }

  // UI helpers
  starsArray(): number[] {
    return [1, 2, 3, 4, 5];
  }

  get ratingAvgText(): string {
    return this.ratingAvg == null ? '—' : this.ratingAvg.toFixed(1);
  }

  isStarFilled(s: number): boolean {
    if (this.ratingAvg == null) return false;
    return this.ratingAvg >= s;
  }

  get ratingBarPct(): number {
    if (!this.ratingCount || this.ratingAvg == null) return 0;
    const pct = (this.ratingAvg / 5) * 100;
    return Math.max(0, Math.min(100, pct));
  }

  alquilar(): void {
    if (!this.vehiculo) return;

    this.router.navigate(['/cotizar/detalle', this.vehiculo.id], {
      queryParams: {
        dias: 1,
        pickupLocationId: 1,
        dropoffLocationId: 1,
      },
    });
  }
}