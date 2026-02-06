import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminLocationsService, AdminLocationDto, AdminLocationCreateUpdate } from '../services/admin-locations.service';

@Component({
  selector: 'app-admin-ubicaciones',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="page-head">
      <div>
        <h2>Ubicaciones</h2>
        <p class="muted">ABM de sucursales (alta, edición y desactivación).</p>
      </div>

      <div class="actions">
        <label class="check">
          <input type="checkbox" [(ngModel)]="showInactive" />
          Mostrar inactivas
        </label>

        <button class="btn" (click)="startCreate()">+ Nueva ubicación</button>
      </div>
    </div>

    <div *ngIf="loading" class="box">Cargando...</div>

    <div *ngIf="error" class="box error">
      {{ error }}
      <button class="btn btn-ghost" (click)="load()">Reintentar</button>
    </div>

    <!-- FORM (crear/editar) -->
    <div *ngIf="mode !== 'none'" class="form-card">
      <div class="form-title">
        <strong>{{ mode === 'create' ? 'Nueva ubicación' : 'Editar ubicación' }}</strong>
        <button class="btn btn-ghost" (click)="cancel()">Cerrar</button>
      </div>

      <div class="grid">
        <label>
          <span>Nombre *</span>
          <input [(ngModel)]="form.name" placeholder="Ej: Aeropuerto Ezeiza" />
        </label>

        <label>
          <span>Ciudad</span>
          <input [(ngModel)]="form.city" placeholder="Ej: Ezeiza" />
        </label>

        <label class="col-2">
          <span>Dirección *</span>
          <input [(ngModel)]="form.address" placeholder="Ej: Autopista Riccheri KM 33.5" />
        </label>

        <label>
          <span>Latitud</span>
          <input type="number" [(ngModel)]="form.latitude" />
        </label>

        <label>
          <span>Longitud</span>
          <input type="number" [(ngModel)]="form.longitude" />
        </label>

        <label class="check">
          <input type="checkbox" [(ngModel)]="form.isActive" />
          Activa
        </label>
      </div>

      <div class="form-actions">
        <button class="btn" [disabled]="saving" (click)="save()">
          {{ saving ? 'Guardando...' : (mode === 'create' ? 'Crear' : 'Guardar cambios') }}
        </button>
      </div>

      <div *ngIf="formError" class="form-error">{{ formError }}</div>
    </div>

    <!-- TABLA -->
    <div class="table-wrap" *ngIf="!loading && locations.length">
      <table class="table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Ciudad</th>
            <th>Dirección</th>
            <th>Lat</th>
            <th>Lon</th>
            <th>Activa</th>
            <th style="width: 210px;">Acciones</th>
          </tr>
        </thead>

        <tbody>
          <tr *ngFor="let l of filteredLocations()">
            <td><strong>{{ l.name }}</strong></td>
            <td>{{ l.city || '-' }}</td>
            <td>{{ l.address || '-' }}</td>
            <td>{{ l.latitude ?? '-' }}</td>
            <td>{{ l.longitude ?? '-' }}</td>
            <td>
              <span class="badge" [class.off]="!l.isActive">{{ l.isActive ? 'Sí' : 'No' }}</span>
            </td>
            <td class="row-actions">
              <button class="btn btn-ghost" (click)="startEdit(l)">Editar</button>
              <button class="btn btn-danger" [disabled]="!l.isActive" (click)="deactivate(l)">
                Desactivar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div *ngIf="!loading && !locations.length" class="box">
      No hay ubicaciones cargadas.
    </div>
  `,
  styles: [`
    .page-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      margin-bottom: 14px;
    }
    .muted { opacity: .8; margin: 6px 0 0; }

    .actions { display: flex; align-items: center; gap: 12px; }
    .check { display: inline-flex; align-items: center; gap: 8px; opacity: .95; }

    .box {
      border: 1px solid rgba(255,255,255,.10);
      background: rgba(255,255,255,.04);
      padding: 12px;
      border-radius: 12px;
      margin: 10px 0;
    }
    .box.error { border-color: rgba(255, 80, 80, .35); }

    .btn {
      border: 1px solid rgba(255,255,255,.16);
      background: rgba(255,255,255,.06);
      color: #e8eefc;
      padding: 8px 12px;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      transition: background .12s ease, opacity .12s ease, transform .1s ease;
    }
    .btn:hover { background: rgba(255,255,255,.10); }
    .btn:active { transform: translateY(1px); }
    .btn[disabled] { opacity: .6; cursor: not-allowed; }

    .btn-ghost { background: transparent; }
    .btn-danger {
      border-color: rgba(255, 90, 90, .35);
      background: rgba(255, 90, 90, .10);
    }
    .btn-danger:hover { background: rgba(255, 90, 90, .16); }

    .form-card {
      border: 1px solid rgba(255,255,255,.10);
      background: rgba(255,255,255,.04);
      padding: 12px;
      border-radius: 14px;
      margin: 12px 0 18px;
    }
    .form-title {
      display:flex;
      align-items:center;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .col-2 { grid-column: span 2; }
    label span { display:block; font-size: 12px; opacity: .8; margin-bottom: 4px; }
    input {
      width: 100%;
      padding: 10px 10px;
      border-radius: 10px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(0,0,0,.15);
      color: #e8eefc;
      outline: none;
    }
    input:focus { border-color: rgba(47,111,237,.55); }

    .form-actions { margin-top: 10px; display:flex; justify-content:flex-end; }
    .form-error { margin-top: 10px; color: #ffb4b4; }

    .table-wrap {
      border: 1px solid rgba(255,255,255,.10);
      border-radius: 14px;
      overflow: hidden;
    }
    .table {
      width: 100%;
      border-collapse: collapse;
      background: rgba(0,0,0,.10);
    }
    th, td {
      padding: 10px 12px;
      border-bottom: 1px solid rgba(255,255,255,.06);
      text-align: left;
      vertical-align: middle;
    }
    th { font-size: 12px; opacity: .85; background: rgba(255,255,255,.04); }
    .row-actions { display:flex; gap: 8px; }

    .badge {
      display:inline-flex;
      padding: 5px 10px;
      border-radius: 999px;
      border: 1px solid rgba(124,58,237,.35);
      background: rgba(124,58,237,.14);
      font-size: 12px;
      font-weight: 700;
    }
    .badge.off {
      border-color: rgba(255,255,255,.18);
      background: rgba(255,255,255,.06);
      opacity: .85;
    }
  `]
})
export class AdminUbicacionesComponent implements OnInit {
  locations: AdminLocationDto[] = [];
  loading = false;
  error = '';

  showInactive = false;

  mode: 'none' | 'create' | 'edit' = 'none';
  editingId: number | null = null;

  saving = false;
  formError = '';

  form: Required<Pick<AdminLocationDto, 'name' | 'city' | 'address' | 'latitude' | 'longitude' | 'isActive'>> = {
    name: '',
    city: '',
    address: '',
    latitude: null,
    longitude: null,
    isActive: true,
  };

  constructor(private api: AdminLocationsService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.error = '';
    this.api.list().subscribe({
      next: (res) => {
        this.locations = res ?? [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.error = 'No se pudieron cargar las ubicaciones.';
      },
    });
  }

  filteredLocations(): AdminLocationDto[] {
    if (this.showInactive) return this.locations;
    return this.locations.filter(l => l.isActive);
  }

  startCreate(): void {
    this.mode = 'create';
    this.editingId = null;
    this.formError = '';
    this.form = {
      name: '',
      city: '',
      address: '',
      latitude: null,
      longitude: null,
      isActive: true,
    };
  }

  startEdit(l: AdminLocationDto): void {
    this.mode = 'edit';
    this.editingId = l.id;
    this.formError = '';
    this.form = {
      name: l.name ?? '',
      city: l.city ?? '',
      address: l.address ?? '',
      latitude: l.latitude ?? null,
      longitude: l.longitude ?? null,
      isActive: !!l.isActive,
    };
  }

  cancel(): void {
    this.mode = 'none';
    this.editingId = null;
    this.formError = '';
  }

  save(): void {
    this.formError = '';

    const name = (this.form.name ?? '').trim();
    const address = (this.form.address ?? '').trim();
    const city = (this.form.city ?? '').trim();

    if (!name) {
      this.formError = 'El nombre es obligatorio.';
      return;
    }
    if (!address) {
      this.formError = 'La dirección es obligatoria.';
      return;
    }

    const payload: AdminLocationCreateUpdate = {
      name,
      address,
      city: city ? city : null,
      latitude: this.form.latitude,
      longitude: this.form.longitude,
      isActive: this.form.isActive,
    };

    this.saving = true;

    if (this.mode === 'create') {
      this.api.create(payload).subscribe({
        next: () => {
          this.saving = false;
          this.mode = 'none';
          this.load();
        },
        error: () => {
          this.saving = false;
          this.formError = 'No se pudo crear la ubicación.';
        },
      });
      return;
    }

    if (this.mode === 'edit' && this.editingId !== null) {
      this.api.update(this.editingId, payload).subscribe({
        next: () => {
          this.saving = false;
          this.mode = 'none';
          this.load();
        },
        error: () => {
          this.saving = false;
          this.formError = 'No se pudo actualizar la ubicación.';
        },
      });
      return;
    }

    this.saving = false;
  }

  deactivate(l: AdminLocationDto): void {
    const ok = window.confirm(`¿Desactivar la sucursal "${l.name}"?`);
    if (!ok) return;

    this.api.deactivate(l.id).subscribe({
      next: () => this.load(),
      error: () => alert('No se pudo desactivar la sucursal.'),
    });
  }
}