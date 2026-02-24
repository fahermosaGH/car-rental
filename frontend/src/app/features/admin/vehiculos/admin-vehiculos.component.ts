import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { catchError, finalize, forkJoin, of } from 'rxjs';

import {
  AdminVehiclesService,
  AdminVehicleDto,
  AdminVehicleCreateUpdate,
} from '../services/admin-vehicles.service';

import { AdminCategoriesService, AdminCategoryDto } from '../services/admin-categories.service';

@Component({
  selector: 'app-admin-vehiculos',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-vehiculos.component.html',
  styleUrls: ['./admin-vehiculos.component.css'],
})
export class AdminVehiculosComponent implements OnInit {
  // data
  items: AdminVehicleDto[] = [];
  categories: AdminCategoryDto[] = [];

  // ui state
  loading = false;
  saving = false;
  error = '';

  showInactive = false;

  // ✅ filtros (front)
  brandFilter = '';
  yearFilter: number | null = null;
  categoryFilter: number | '' = '';
  filteredItems: AdminVehicleDto[] = [];

  modalOpen = false;
  editing: AdminVehicleDto | null = null;

  form: AdminVehicleCreateUpdate = this.emptyForm();

  constructor(
    private api: AdminVehiclesService,
    private catsApi: AdminCategoriesService
  ) {}

  ngOnInit(): void {
    this.load();
  }

  private emptyForm(): AdminVehicleCreateUpdate {
    return {
      brand: '',
      model: '',
      year: null,
      seats: null,
      transmission: null,
      categoryId: null,
      dailyPrice: null,
      isActive: true,
      imageUrl: null,
    };
  }

  load(): void {
    this.error = '';
    this.loading = true;

    forkJoin({
      vehicles: this.api.list(this.showInactive).pipe(
        catchError((e) => {
          this.error =
            e?.error?.error ??
            e?.error?.message ??
            'No se pudieron cargar los vehículos.';
          return of([] as AdminVehicleDto[]);
        })
      ),
      categories: this.catsApi.list().pipe(catchError(() => of([] as AdminCategoryDto[]))),
    })
      .pipe(finalize(() => (this.loading = false)))
      .subscribe(({ vehicles, categories }) => {
        this.items = vehicles ?? [];
        this.categories = categories ?? [];
        this.applyFilters(); // ✅ recalcula tabla
      });
  }

  // ✅ aplicar filtros (front)
  applyFilters(): void {
    const brandQ = this.brandFilter.trim().toLowerCase();
    const yearQ = this.yearFilter !== null && Number.isFinite(this.yearFilter) ? Number(this.yearFilter) : null;
    const catQ = this.categoryFilter === '' ? null : Number(this.categoryFilter);

    this.filteredItems = (this.items ?? []).filter((v) => {
      if (brandQ) {
        const b = (v.brand ?? '').toLowerCase();
        if (!b.includes(brandQ)) return false;
      }

      if (yearQ !== null) {
        if ((v.year ?? null) !== yearQ) return false;
      }

      if (catQ !== null) {
        if ((v.categoryId ?? null) !== catQ) return false;
      }

      return true;
    });
  }

  clearFilters(): void {
    this.brandFilter = '';
    this.yearFilter = null;
    this.categoryFilter = '';
    this.applyFilters();
  }

  openCreate(): void {
    this.editing = null;
    this.form = this.emptyForm();
    this.modalOpen = true;
    this.error = '';

    if (this.categories.length > 0 && this.form.categoryId == null) {
      this.form.categoryId = this.categories[0].id;
    }
  }

  openEdit(v: AdminVehicleDto): void {
    this.editing = v;
    this.error = '';

    this.form = {
      brand: v.brand ?? '',
      model: v.model ?? '',
      year: v.year ?? null,
      seats: v.seats ?? null,
      transmission: v.transmission ?? null,
      categoryId: v.categoryId ?? null,
      dailyPrice: v.dailyPrice ?? null,
      isActive: !!v.isActive,
      imageUrl: v.imageUrl ?? null,
    };

    this.modalOpen = true;

    if (this.categories.length > 0 && this.form.categoryId == null) {
      this.form.categoryId = this.categories[0].id;
    }
  }

  closeModal(): void {
    this.modalOpen = false;
  }

  save(): void {
    this.error = '';

    const payload: AdminVehicleCreateUpdate = {
      brand: (this.form.brand ?? '').trim(),
      model: (this.form.model ?? '').trim(),
      year: this.form.year !== null ? Number(this.form.year) : null,
      seats: this.form.seats !== null ? Number(this.form.seats) : null,
      transmission: (this.form.transmission ?? '').trim() || null,
      categoryId: this.form.categoryId !== null ? Number(this.form.categoryId) : null,
      dailyPrice: this.form.dailyPrice !== null ? Number(this.form.dailyPrice) : null,
      isActive: !!this.form.isActive,
      imageUrl: (this.form.imageUrl ?? '').trim() || null,
    };

    if (!payload.brand) { this.error = 'Marca es obligatoria.'; return; }
    if (!payload.model) { this.error = 'Modelo es obligatorio.'; return; }

    if (payload.year === null || !Number.isFinite(payload.year) || payload.year < 1900) {
      this.error = 'Año inválido (mínimo 1900).';
      return;
    }

    if (payload.seats === null || !Number.isFinite(payload.seats) || payload.seats <= 0) {
      this.error = 'Asientos es obligatorio (número > 0).';
      return;
    }

    if (!payload.transmission) {
      this.error = 'Transmisión es obligatoria.';
      return;
    }

    if (payload.categoryId === null || !Number.isFinite(payload.categoryId) || payload.categoryId <= 0) {
      this.error = 'Categoría es obligatoria.';
      return;
    }

    if (this.categories.length > 0) {
      const exists = this.categories.some((c) => c.id === payload.categoryId);
      if (!exists) {
        this.error = 'La categoría seleccionada no existe (recargá la lista).';
        return;
      }
    }

    this.saving = true;

    const req = this.editing
      ? this.api.update(this.editing.id, payload)
      : this.api.create(payload);

    req.pipe(finalize(() => (this.saving = false))).subscribe({
      next: () => {
        this.closeModal();
        this.load();
      },
      error: (e) => {
        this.error = e?.error?.error ?? e?.error?.message ?? 'No se pudo guardar.';
      },
    });
  }

  deactivate(v: AdminVehicleDto): void {
    if (!confirm(`Desactivar vehículo #${v.id}?`)) return;

    this.error = '';
    this.loading = true;

    this.api.deactivate(v.id)
      .pipe(finalize(() => (this.loading = false)))
      .subscribe({
        next: () => this.load(),
        error: (e) => {
          this.error = e?.error?.error ?? e?.error?.message ?? 'No se pudo desactivar.';
        },
      });
  }
}