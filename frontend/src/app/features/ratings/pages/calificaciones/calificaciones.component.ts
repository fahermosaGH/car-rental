import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';

import { CotizarService } from '../../../cotizar/services/cotizar.service';
import { VehicleOption } from '../../../cotizar/models/quote';

type SortMode = 'best' | 'mostReviewed';

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

  items: VehicleOption[] = [];
  filtered: any[] = [];

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
        this.items = Array.isArray(data) ? data : [];
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
      const name = `${v.brand ?? ''} ${v.model ?? ''}`.toLowerCase();
      const cat = (v.category ?? '').toLowerCase();
      return name.includes(q) || cat.includes(q);
    });

    const withScore = base.map((v) => {
      const avg = typeof (v as any).ratingAvg === 'number' ? (v as any).ratingAvg : null;
      const cnt = typeof (v as any).ratingCount === 'number' ? (v as any).ratingCount : 0;

      return {
        ...v,
        __avg: avg,
        __cnt: cnt,
        __score: avg == null ? -1 : avg, // “sin reseñas” al final
      };
    });

    if (this.sort === 'best') {
      withScore.sort((a: any, b: any) => {
        if (b.__score !== a.__score) return b.__score - a.__score;
        if (b.__cnt !== a.__cnt) return b.__cnt - a.__cnt;
        return String(a.name ?? '').localeCompare(String(b.name ?? ''));
      });
    } else {
      withScore.sort((a: any, b: any) => {
        if (b.__cnt !== a.__cnt) return b.__cnt - a.__cnt;
        if (b.__score !== a.__score) return b.__score - a.__score;
        return String(a.name ?? '').localeCompare(String(b.name ?? ''));
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

  avgOf(v: any): number | null {
    return typeof v.__avg === 'number' ? v.__avg : null;
  }

  cntOf(v: any): number {
    return typeof v.__cnt === 'number' ? v.__cnt : 0;
  }

  avgText(v: any): string {
    const a = this.avgOf(v);
    return a == null ? '—' : a.toFixed(1);
  }

  isStarFilled(v: any, s: number): boolean {
    const a = this.avgOf(v);
    if (a == null) return false;
    return a >= s;
  }
}