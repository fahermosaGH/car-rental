import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  AdminVehicleUnitsService,
  VehicleUnitRowDto,
  VehicleUnitStatus,
  VehicleUnitCreatePayload,
} from '../services/admin-vehicle-units.service';
import { AdminVehiclesService, AdminVehicleDto } from '../services/admin-vehicles.service';
import { AdminLocationsService, AdminLocationDto } from '../services/admin-locations.service';
import { catchError, finalize, forkJoin, of } from 'rxjs';

@Component({
  selector: 'app-admin-unidades',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-unidades.component.html',
  styleUrls: ['./admin-unidades.component.css'],
})
export class AdminUnidadesComponent implements OnInit {
  // data
  rows: VehicleUnitRowDto[] = [];
  vehicles: AdminVehicleDto[] = [];
  locations: AdminLocationDto[] = [];

  // ui state
  loading = false;
  error = false;

  creating = false;
  createError = '';
  deleting: Record<number, boolean> = {};
  updating: Record<number, boolean> = {};

  // filters
  fVehicleId = 0;
  fLocationId = 0;
  fStatus: '' | VehicleUnitStatus = '';

  // create form
  plate = '';
  vehicleId = 0;
  locationId = 0;
  status: VehicleUnitStatus = 'available';

  // inline edit drafts
  edit: Record<number, { plate: string; status: VehicleUnitStatus }> = {};

  readonly statuses: { value: VehicleUnitStatus; label: string }[] = [
    { value: 'available', label: 'Disponible' },
    { value: 'maintenance', label: 'Mantenimiento' },
    { value: 'inactive', label: 'Inactiva' },
  ];

  // ✅ formatos válidos Argentina:
  // - AAA111 (viejo)
  // - AA111AA (Mercosur)
  private readonly PLATE_AR = /^(?:[A-Z]{3}\d{3}|[A-Z]{2}\d{3}[A-Z]{2})$/;

  constructor(
    private units: AdminVehicleUnitsService,
    private vehiclesSrv: AdminVehiclesService,
    private locationsSrv: AdminLocationsService
  ) {}

  ngOnInit(): void {
    this.bootstrap();
  }

  bootstrap(): void {
    this.loading = true;
    this.error = false;

    forkJoin({
      vehicles: this.vehiclesSrv.list().pipe(catchError(() => of([] as AdminVehicleDto[]))),
      locations: this.locationsSrv.list().pipe(catchError(() => of([] as AdminLocationDto[]))),
    })
      .pipe(finalize(() => (this.loading = false)))
      .subscribe({
        next: ({ vehicles, locations }) => {
          this.vehicles = vehicles;
          this.locations = locations;

          if (this.vehicles.length && this.vehicleId === 0) this.vehicleId = this.vehicles[0].id;
          if (this.locations.length && this.locationId === 0) this.locationId = this.locations[0].id;

          this.load();
        },
        error: () => {
          this.error = true;
        },
      });
  }

  load(): void {
    this.loading = true;
    this.error = false;

    const params: any = {};
    if (this.fVehicleId > 0) params.vehicleId = this.fVehicleId;
    if (this.fLocationId > 0) params.locationId = this.fLocationId;
    if (this.fStatus) params.status = this.fStatus;

    this.units
      .list(params)
      .pipe(
        catchError(() => {
          this.error = true;
          return of([] as VehicleUnitRowDto[]);
        }),
        finalize(() => (this.loading = false))
      )
      .subscribe((rows) => {
        this.rows = rows ?? [];

        const nextEdit: Record<number, { plate: string; status: VehicleUnitStatus }> = {};
        this.rows.forEach((r) => {
          nextEdit[r.id] = {
            plate: (r.plate ?? '').toString(),
            status: (r.status as VehicleUnitStatus) ?? 'available',
          };
        });
        this.edit = nextEdit;
      });
  }

  clearFilters(): void {
    this.fVehicleId = 0;
    this.fLocationId = 0;
    this.fStatus = '';
    this.load();
  }

  // ✅ Normaliza (saca espacios/guiones y uppercase)
  private normalizePlate(raw: string): string {
    return (raw ?? '')
      .toUpperCase()
      .replace(/[^A-Z0-9]/g, ''); // elimina espacios, guiones, puntos, etc.
  }

  private isValidPlateAR(raw: string): boolean {
    const plate = this.normalizePlate(raw);
    return this.PLATE_AR.test(plate);
  }

  // CREATE
  create(): void {
    this.createError = '';

    const plateNorm = this.normalizePlate(this.plate);
    if (!plateNorm) {
      this.createError = 'La patente es obligatoria.';
      return;
    }
    if (!this.isValidPlateAR(plateNorm)) {
      this.createError = 'Formato inválido. Usá AAA111 o AA111AA (Argentina).';
      return;
    }
    if (this.vehicleId <= 0) {
      this.createError = 'Seleccioná un vehículo.';
      return;
    }
    if (this.locationId <= 0) {
      this.createError = 'Seleccioná una sucursal.';
      return;
    }

    const payload: VehicleUnitCreatePayload = {
      plate: plateNorm,
      vehicleId: this.vehicleId,
      locationId: this.locationId,
      status: this.status,
    };

    this.creating = true;
    this.units
      .create(payload)
      .pipe(finalize(() => (this.creating = false)))
      .subscribe({
        next: () => {
          this.plate = '';
          this.load();
        },
        error: (err) => {
          if (err?.status === 409) {
            this.createError = 'Esa patente ya existe.';
          } else if (err?.error?.error) {
            this.createError = err.error.error;
          } else {
            this.createError = 'No se pudo crear la unidad.';
          }
        },
      });
  }

  // SAVE (usa edit[id] que está bindeado por ngModel)
  saveRow(row: VehicleUnitRowDto): void {
    const draft = this.edit[row.id];
    if (!draft) return;

    const plateNorm = this.normalizePlate(draft.plate);
    if (!plateNorm) {
      alert('La patente no puede estar vacía.');
      return;
    }
    if (!this.isValidPlateAR(plateNorm)) {
      alert('Formato inválido. Usá AAA111 o AA111AA (Argentina).');
      return;
    }

    this.updating[row.id] = true;
    this.units
      .update(row.id, { plate: plateNorm, status: draft.status })
      .pipe(finalize(() => (this.updating[row.id] = false)))
      .subscribe({
        next: () => {
          row.plate = plateNorm;
          row.status = draft.status;
          // también actualizamos el draft para que quede normalizado
          this.edit[row.id].plate = plateNorm;
        },
        error: (err) => {
          if (err?.status === 409) alert('Esa patente ya existe.');
          else alert('No se pudo actualizar la unidad.');
          this.load();
        },
      });
  }

  // DELETE
  removeRow(row: VehicleUnitRowDto): void {
    if (!confirm(`Eliminar la unidad ${row.plate}?`)) return;

    this.deleting[row.id] = true;
    this.units
      .delete(row.id)
      .pipe(finalize(() => (this.deleting[row.id] = false)))
      .subscribe({
        next: () => this.load(),
        error: () => alert('No se pudo eliminar la unidad.'),
      });
  }
}