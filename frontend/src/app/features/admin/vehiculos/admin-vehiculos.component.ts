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

  form: AdminVehicleCreateUpdate = {
    brand: '',
    model: '',
    year: null,
    dailyPrice: null,
    isActive: true,
    imageUrl: '',
  };

  constructor(private api: AdminVehiclesService) {}

  ngOnInit(): void {
    this.load();
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
          this.error =
            e?.error?.message ?? 'No se pudieron cargar los vehículos.';
        },
      });
  }

  openCreate(): void {
    this.editing = null;
    this.form = {
      brand: '',
      model: '',
      year: null,
      dailyPrice: null,
      isActive: true,
      imageUrl: '',
    };
    this.modalOpen = true;
  }

  openEdit(v: AdminVehicleDto): void {
    this.editing = v;
    this.form = {
      brand: v.brand ?? '',
      model: v.model ?? '',
      year: v.year ?? null,
      dailyPrice: v.dailyPrice ?? null,
      isActive: v.isActive,
      imageUrl: v.imageUrl ?? '',
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
      dailyPrice: this.form.dailyPrice ?? null,
      isActive: !!this.form.isActive,
      imageUrl: (this.form.imageUrl ?? '').trim() || null,
    };

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
        this.error = e?.error?.message ?? 'No se pudo guardar.';
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
