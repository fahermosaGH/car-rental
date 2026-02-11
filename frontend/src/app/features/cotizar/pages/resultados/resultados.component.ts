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
  styleUrls: ['./resultados.component.css'],
})
export class ResultadosComponent implements OnInit {
  resultados: VehicleOption[] = [];
  dias = 1;

  startAt = '';
  endAt = '';
  pickupLocationId = 1;
  dropoffLocationId = 1;

  cargando = true;

  order: 'asc' | 'desc' = 'asc';

  availableCategories: string[] = ['Todos'];
  selectedCategory: string = 'Todos';

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cotizarService: CotizarService
  ) {}

  ngOnInit() {
    this.route.queryParamMap.subscribe((params) => {
      this.startAt = params.get('startAt') || '';
      this.endAt = params.get('endAt') || '';
      this.pickupLocationId = +(params.get('pickupLocationId') || 1);
      this.dropoffLocationId = +(params.get('dropoffLocationId') || 1);

      const cat = params.get('category');
      this.selectedCategory = cat && cat.trim() !== '' ? cat : 'Todos';

      if (this.startAt && this.endAt) {
        const diffMs =
          new Date(this.endAt).getTime() - new Date(this.startAt).getTime();
        this.dias = Math.max(1, Math.ceil(diffMs / 86400000));
      } else {
        this.dias = 1;
      }

      this.cargarResultados();
    });
  }

  private refrescarCategoriasDisponibles(): void {
    const categoriasUnicas = Array.from(
      new Set(
        this.resultados
          .map((r) => r.category)
          .filter((c): c is string => !!c && c.trim() !== '')
      )
    );

    this.availableCategories = ['Todos', ...categoriasUnicas];

    if (
      this.selectedCategory !== 'Todos' &&
      !categoriasUnicas.includes(this.selectedCategory)
    ) {
      this.selectedCategory = 'Todos';
    }
  }

  private cargarResultados(): void {
    this.cargando = true;

    // Si faltan params esenciales, no hay forma de calcular “disponibles”
    if (!this.startAt || !this.endAt || !this.pickupLocationId) {
      this.resultados = [];
      this.refrescarCategoriasDisponibles();
      this.cargando = false;
      return;
    }

    const category =
      this.selectedCategory && this.selectedCategory !== 'Todos'
        ? this.selectedCategory
        : undefined;

    this.cotizarService
      .getAvailableVehicles({
        pickupLocationId: this.pickupLocationId,
        startAt: this.startAt,
        endAt: this.endAt,
        category,
      })
      .subscribe({
        next: (data) => {
          this.resultados = data ?? [];
          this.refrescarCategoriasDisponibles();
          this.ordenarPorPrecio(this.order);
          this.cargando = false;
        },
        error: () => {
          this.resultados = [];
          this.refrescarCategoriasDisponibles();
          this.cargando = false;
        },
      });
  }

  ordenarPorPrecio(dir: 'asc' | 'desc') {
    this.order = dir;
    this.resultados = [...this.resultados].sort((a, b) => {
      const da = a.dailyRate ?? 0;
      const db = b.dailyRate ?? 0;
      return dir === 'asc' ? da - db : db - da;
    });
  }

  cambiarCategoria(cat: string) {
    this.selectedCategory = cat;

    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { category: cat !== 'Todos' ? cat : null },
      queryParamsHandling: 'merge',
    });
  }

  calcularTotal(rate: number) {
    return rate * this.dias;
  }

  verDetalle(id: number) {
    this.router.navigate(['/cotizar/detalle', id], {
      queryParams: {
        dias: this.dias,
        startAt: this.startAt,
        endAt: this.endAt,
        pickupLocationId: this.pickupLocationId,
        dropoffLocationId: this.dropoffLocationId,
      },
    });
  }
}