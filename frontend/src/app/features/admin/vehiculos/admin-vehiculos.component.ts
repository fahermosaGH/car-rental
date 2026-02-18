import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { finalize } from 'rxjs';

import {
  AdminVehiclesService,
  AdminVehicleDto,
  AdminVehicleCreateUpdate,
} from '../services/admin-vehicles.service';

@Component({
  selector: 'app-admin-vehiculos',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-vehiculos.component.html',
  styleUrls: ['./admin-vehiculos.component.css'],
})
export class AdminVehiculosComponent implements OnInit {
  loading = false;
  error = '';
  items: AdminVehicleDto[] = [];

  showInactive = false;

  modalOpen = false;
  editing: AdminVehicleDto | null = null;

  form: AdminVehicleCreateUpdate = this.emptyForm();

  constructor(private api: AdminVehiclesService) {}

  ngOnInit(): void {
    this.load();
  }

  private emptyForm(): AdminVehicleCreateUpdate {
    return {
      brand: '',
      model: '',
      year: null,
      seats: null,
      transmission: '',
      categoryId: 0,
      dailyPrice: null,
      isActive: true,
      imageUrl: null,
    };
  }

  load(): void {
    this.error = '';
    this.loading = true;

    this.api
      .list(this.showInactive)
      .pipe(finalize(() => (this.loading = false)))
      .subscribe({
        next: (data) => (this.items = data ?? []),
        error: (e) => {
          this.error = e?.error?.message ?? 'No se pudieron cargar los vehículos.';
        },
      });
  }

  openCreate(): void {
    this.editing = null;
    this.form = this.emptyForm();
    this.modalOpen = true;
  }

  openEdit(v: AdminVehicleDto): void {
    this.editing = v;

    this.form = {
      brand: v.brand ?? '',
      model: v.model ?? '',
      year: v.year ?? null,
      seats: v.seats ?? null,
      transmission: v.transmission ?? '',
      categoryId: v.categoryId ?? 0,
      dailyPrice: v.dailyPrice ?? null,
      isActive: !!v.isActive,
      imageUrl: v.imageUrl ?? null,
    };

    this.modalOpen = true;
  }

  closeModal(): void {
    this.modalOpen = false;
  }

  save(): void {
    this.error = '';

    const payload: AdminVehicleCreateUpdate = {
      brand: (this.form.brand ?? '').trim(),
      model: (this.form.model ?? '').trim(),
      year: this.form.year ?? null,
      seats: this.form.seats ?? null,
      transmission: (this.form.transmission ?? '').trim(),
      categoryId: Number(this.form.categoryId ?? 0),
      dailyPrice: this.form.dailyPrice ?? null,
      isActive: !!this.form.isActive,
      imageUrl: (this.form.imageUrl ?? '').trim() || null,
    };

    // ✅ Validaciones para no pegarle al backend con nulls
    if (!payload.brand) {
      this.error = 'Marca es obligatoria.';
      return;
    }
    if (!payload.model) {
      this.error = 'Modelo es obligatorio.';
      return;
    }
    if (!payload.year || payload.year <= 0) {
      this.error = 'Año es obligatorio.';
      return;
    }
    if (!payload.seats || payload.seats <= 0) {
      this.error = 'Asientos es obligatorio (número > 0).';
      return;
    }
    if (!payload.transmission) {
      this.error = 'Transmisión es obligatoria.';
      return;
    }
    if (!payload.categoryId || payload.categoryId <= 0) {
      this.error = 'Categoría (ID) es obligatoria.';
      return;
    }

    this.loading = true;

    const req = this.editing
      ? this.api.update(this.editing.id, payload)
      : this.api.create(payload);

    req.pipe(finalize(() => (this.loading = false))).subscribe({
      next: () => {
        this.closeModal();
        this.load();
      },
      error: (e) => {
        // backend suele mandar {error: "..."} o {message: "..."}
        this.error = e?.error?.error ?? e?.error?.message ?? 'No se pudo guardar.';
      },
    });
  }

  deactivate(v: AdminVehicleDto): void {
    if (!confirm(`Desactivar vehículo #${v.id}?`)) return;

    this.loading = true;
    this.api
      .deactivate(v.id)
      .pipe(finalize(() => (this.loading = false)))
      .subscribe({
        next: () => this.load(),
        error: (e) => {
          this.error = e?.error?.message ?? 'No se pudo desactivar.';
        },
      });
  }
}

