import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';

import { CotizarService } from '../../../cotizar/services/cotizar.service';

type SortMode = 'best' | 'mostReviewed';

// ✅ Modelo real que devuelve /api/vehicles (lo que vos ves en curl)
type VehicleApi = {
  id: number;
  brand: string;
  model: string;
  year?: number | null;
  seats?: number | null;
  transmission?: string | null;
  dailyRate?: number | string | null;
  isActive?: boolean;
  category?: string | null;

  // ✅ vienen del backend
  ratingAvg?: number | string | null;
  ratingCount?: number | string | null;

  // (opcional) si en algún lado tenés img
  img?: string | null;
};

type UiVehicle = VehicleApi & {
  __name: string;
  __img?: string | null;
  __avg: number | null;
  __cnt: number;
  __score: number; // para ordenar
};

@Component({
  selector: 'app-calificaciones',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './calificaciones.component.html',
  styleUrls: ['./calificaciones.component.css'],
})
export class CalificacionesComponent implements OnInit {
  loading = true;
  error = '';

  items: VehicleApi[] = [];
  filtered: UiVehicle[] = [];

  q = '';
  sort: SortMode = 'best';

  constructor(private cotizarService: CotizarService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.error = '';

    this.cotizarService.buscarVehiculos().subscribe({
      next: (data) => {
        this.items = Array.isArray(data) ? (data as VehicleApi[]) : [];
        this.apply();
        this.loading = false;
      },
      error: () => {
        this.items = [];
        this.filtered = [];
        this.loading = false;
        this.error = 'No se pudieron cargar las calificaciones.';
      },
    });
  }

  apply(): void {
    const q = (this.q || '').trim().toLowerCase();

    const base = this.items.filter((v) => {
      if (!q) return true;

      const brand = String(v.brand ?? '');
      const model = String(v.model ?? '');
      const name = `${brand} ${model}`.trim();
      const cat = String(v.category ?? '');

      return (
        name.toLowerCase().includes(q) ||
        cat.toLowerCase().includes(q) ||
        brand.toLowerCase().includes(q) ||
        model.toLowerCase().includes(q)
      );
    });

    const withScore: UiVehicle[] = base.map((v) => {
      const name = `${v.brand ?? ''} ${v.model ?? ''}`.trim() || 'Vehículo';

      // soportar number o string
      const rawAvg = v.ratingAvg ?? null;
      const rawCnt = v.ratingCount ?? 0;

      const avg =
        rawAvg === null || rawAvg === undefined
          ? null
          : typeof rawAvg === 'number'
          ? rawAvg
          : Number.parseFloat(String(rawAvg));

      const cnt =
        typeof rawCnt === 'number'
          ? rawCnt
          : Number.parseInt(String(rawCnt), 10) || 0;

      const safeAvg = Number.isFinite(avg as number) ? (avg as number) : null;
      const safeCnt = Number.isFinite(cnt) ? cnt : 0;

      // sin reseñas -> al final
      const score = safeAvg == null ? -1 : safeAvg;

      return {
        ...v,
        __name: name,
        __img: v.img ?? null,
        __avg: safeAvg,
        __cnt: safeCnt,
        __score: score,
      };
    });

    if (this.sort === 'best') {
      withScore.sort((a, b) => {
        if (b.__score !== a.__score) return b.__score - a.__score;
        if (b.__cnt !== a.__cnt) return b.__cnt - a.__cnt;
        return a.__name.localeCompare(b.__name);
      });
    } else {
      withScore.sort((a, b) => {
        if (b.__cnt !== a.__cnt) return b.__cnt - a.__cnt;
        if (b.__score !== a.__score) return b.__score - a.__score;
        return a.__name.localeCompare(b.__name);
      });
    }

    this.filtered = withScore;
  }

  setSort(mode: SortMode): void {
    this.sort = mode;
    this.apply();
  }

  starsArray(): number[] {
    return [1, 2, 3, 4, 5];
  }

  avgOf(v: UiVehicle): number | null {
    return typeof v.__avg === 'number' ? v.__avg : null;
  }

  cntOf(v: UiVehicle): number {
    return typeof v.__cnt === 'number' ? v.__cnt : 0;
  }

  avgText(v: UiVehicle): string {
    const a = this.avgOf(v);
    return a == null ? '—' : a.toFixed(1);
  }

  isStarFilled(v: UiVehicle, s: number): boolean {
    const a = this.avgOf(v);
    if (a == null) return false;
    return a >= s;
  }
}