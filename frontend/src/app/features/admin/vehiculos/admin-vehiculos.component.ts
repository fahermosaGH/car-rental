import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { catchError, finalize, forkJoin, of } from 'rxjs';

import {
  AdminVehiclesService,
  AdminVehicleDto,
  AdminVehicleCreateUpdate,
} from '../services/admin-vehicles.service';

import {
  AdminCategoriesService,
  AdminCategoryDto
} from '../services/admin-categories.service';

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
  loading = false; // carga tabla / requests generales
  saving = false;  // guardar crear/editar
  error = '';

  showInactive = false;

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

  // ✅ Reemplazo total de load(): trae vehículos + categorías
  load(): void {
    this.error = '';
    this.loading = true;

    forkJoin({
      vehicles: this.api.list(this.showInactive).pipe(
        catchError((e) => {
          // si falla vehículos, mostrá error; devolvemos [] para no romper el forkJoin
          this.error =
            e?.error?.error ??
            e?.error?.message ??
            'No se pudieron cargar los vehículos.';
          return of([] as AdminVehicleDto[]);
        })
      ),
      categories: this.catsApi.list().pipe(
        catchError(() => of([] as AdminCategoryDto[]))
      ),
    })
      .pipe(finalize(() => (this.loading = false)))
      .subscribe(({ vehicles, categories }) => {
        this.items = vehicles ?? [];
        this.categories = categories ?? [];
      });
  }

  openCreate(): void {
    this.editing = null;
    this.form = this.emptyForm();
    this.modalOpen = true;
    this.error = '';

    // default categoría si hay alguna
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

    // si el vehículo no tiene categoría por algún dato viejo, seteamos una default
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
      categoryId:
        this.form.categoryId !== null ? Number(this.form.categoryId) : null,
      dailyPrice:
        this.form.dailyPrice !== null ? Number(this.form.dailyPrice) : null,
      isActive: !!this.form.isActive,
      imageUrl: (this.form.imageUrl ?? '').trim() || null,
    };

    // ✅ Validaciones mínimas (evita 500 por NOT NULL)
    if (!payload.brand) { this.error = 'Marca es obligatoria.'; return; }
    if (!payload.model) { this.error = 'Modelo es obligatorio.'; return; }

    if (
      payload.year === null ||
      !Number.isFinite(payload.year) ||
      payload.year < 1900
    ) {
      this.error = 'Año inválido (mínimo 1900).';
      return;
    }

    if (
      payload.seats === null ||
      !Number.isFinite(payload.seats) ||
      payload.seats <= 0
    ) {
      this.error = 'Asientos es obligatorio (número > 0).';
      return;
    }

    if (!payload.transmission) {
      this.error = 'Transmisión es obligatoria.';
      return;
    }

    if (
      payload.categoryId === null ||
      !Number.isFinite(payload.categoryId) ||
      payload.categoryId <= 0
    ) {
      this.error = 'Categoría es obligatoria.';
      return;
    }

    // (opcional) validar que categoryId exista en la lista cargada
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
        this.error =
          e?.error?.error ?? e?.error?.message ?? 'No se pudo guardar.';
      },
    });
  }

  deactivate(v: AdminVehicleDto): void {
    if (!confirm(`Desactivar vehículo #${v.id}?`)) return;

    this.error = '';
    this.loading = true;

    this.api
      .deactivate(v.id)
      .pipe(finalize(() => (this.loading = false)))
      .subscribe({
        next: () => this.load(),
        error: (e) => {
          this.error =
            e?.error?.error ?? e?.error?.message ?? 'No se pudo desactivar.';
        },
      });
  }
}