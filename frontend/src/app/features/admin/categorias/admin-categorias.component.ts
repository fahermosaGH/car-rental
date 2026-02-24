import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminCategoriasService, AdminCategoryRow } from '../services/admin-categorias.service';

@Component({
  selector: 'app-admin-categorias',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-categorias.component.html',
  styleUrls: ['./admin-categorias.component.css'],
})
export class AdminCategoriasComponent implements OnInit {
  loading = true;
  error = '';
  rows: AdminCategoryRow[] = [];

  showInactive = false;
  search = '';

  // ✅ controla visibilidad del form
  showForm = false;

  // form
  editingId: number | null = null;
  formName = '';
  formDescription = '';
  formDailyPrice: string = '';
  formIsActive = true;

  constructor(private api: AdminCategoriasService) {}

  ngOnInit(): void {
    this.reload();
  }

  reload(): void {
    this.loading = true;
    this.error = '';

    this.api
      .list({
        showInactive: this.showInactive,
        search: this.search || undefined,
      })
      .subscribe({
        next: (data) => {
          this.rows = Array.isArray(data) ? data : [];
          this.loading = false;
        },
        error: () => {
          this.error = 'No se pudieron cargar las categorías.';
          this.loading = false;
        },
      });
  }

  startCreate(): void {
    this.editingId = null;
    this.formName = '';
    this.formDescription = '';
    this.formDailyPrice = '';
    this.formIsActive = true;
    this.error = '';
    this.showForm = true;
  }

  startEdit(r: AdminCategoryRow): void {
    this.editingId = r.id;
    this.formName = r.name ?? '';
    this.formDescription = r.description ?? '';
    this.formDailyPrice =
      r.dailyPrice !== null && r.dailyPrice !== undefined ? String(r.dailyPrice) : '';
    this.formIsActive = !!r.isActive;
    this.error = '';
    this.showForm = true;
  }

  cancelForm(): void {
    this.editingId = null;
    this.formName = '';
    this.formDescription = '';
    this.formDailyPrice = '';
    this.formIsActive = true;
    this.showForm = false;
  }

  save(): void {
    const name = (this.formName || '').trim();
    if (!name) {
      this.error = 'El nombre es obligatorio.';
      return;
    }

    const payload = {
      name,
      description: this.formDescription?.trim() ? this.formDescription.trim() : null,
      dailyPrice: this.formDailyPrice?.trim() ? this.formDailyPrice.trim() : null,
      isActive: this.formIsActive,
    };

    const req = this.editingId ? this.api.update(this.editingId, payload) : this.api.create(payload);

    req.subscribe({
      next: () => {
        this.cancelForm();
        this.reload();
      },
      error: (err) => {
        const msg = err?.error?.message;
        this.error = msg ? String(msg) : 'No se pudo guardar la categoría.';
      },
    });
  }

  toggle(r: AdminCategoryRow): void {
    const prev = r.isActive;
    r.isActive = !prev;

    this.api.toggle(r.id).subscribe({
      next: (res) => (r.isActive = !!res.isActive),
      error: () => {
        r.isActive = prev;
        this.error = 'No se pudo actualizar el estado.';
      },
    });
  }

  remove(r: AdminCategoryRow): void {
    if (!confirm(`Eliminar categoría "${r.name}"?`)) return;

    this.api.remove(r.id).subscribe({
      next: () => this.reload(),
      error: (err) => {
        if (err?.status === 409) {
          const count = err?.error?.vehiclesCount;
          this.error = `No se puede eliminar: tiene vehículos asociados${
            count ? ` (${count})` : ''
          }.`;
          return;
        }
        this.error = 'No se pudo eliminar la categoría.';
      },
    });
  }
}
