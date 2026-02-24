import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminAuditoriaService, AuditRow } from '../services/admin-auditoria.service';

@Component({
  selector: 'app-admin-auditoria',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-auditoria.component.html',
  styleUrls: ['./admin-auditoria.component.css'],
})
export class AdminAuditoriaComponent implements OnInit {
  loading = true;
  error = '';
  rows: AuditRow[] = [];

  actor = '';
  action: '' | 'create' | 'update' | 'delete' | 'custom' = '';
  entity = '';
  entityId = '';

  entities = [
    { label: '(todas)', value: '' },
    { label: 'Vehículo', value: 'App\\Entity\\Vehicle' },
    { label: 'Unidad', value: 'App\\Entity\\VehicleUnit' },
    { label: 'Reserva', value: 'App\\Entity\\Reservation' },
    { label: 'Incidente', value: 'App\\Entity\\ReservationIncident' },
    { label: 'Categoría', value: 'App\\Entity\\VehicleCategory' },
  ];

  constructor(private api: AdminAuditoriaService) {}

  ngOnInit(): void {
    this.reload();
  }

  reload(): void {
    this.loading = true;
    this.error = '';

    this.api
      .list({
        actor: this.actor || undefined,
        action: this.action || undefined,
        entity: this.entity || undefined,
        entityId: this.entityId || undefined,
      })
      .subscribe({
        next: (data: AuditRow[]) => {
          this.rows = Array.isArray(data) ? data : [];
          this.loading = false;
        },
        error: () => {
          this.error = 'No se pudo cargar la auditoría.';
          this.loading = false;
        },
      });
  }

  summarizeChanges(changes: any | null): string {
    if (!changes) return '-';
    const keys = Object.keys(changes);
    if (!keys.length) return '-';
    const preview = keys.slice(0, 3).join(', ');
    return keys.length > 3 ? `${preview}…` : preview;
  }
}