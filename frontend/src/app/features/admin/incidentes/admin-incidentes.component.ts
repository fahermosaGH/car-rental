import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminIncidentesService, AdminIncidentRow } from './admin-incidentes.service';

@Component({
  selector: 'app-admin-incidentes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-incidentes.component.html',
  styleUrls: ['./admin-incidentes.component.css'],
})
export class AdminIncidentesComponent implements OnInit {
  loading = true;
  errorMsg = '';

  statusFilter: '' | 'open' | 'resolved' = 'open';
  rows: AdminIncidentRow[] = [];

  // UI reassign
  reassigningId: number | null = null;
  newUnitIdInput: Record<number, string> = {};
  markMaintenance: Record<number, boolean> = {};

  constructor(private api: AdminIncidentesService) {}

  ngOnInit(): void {
    this.cargar();
  }

  cargar(): void {
    this.loading = true;
    this.errorMsg = '';

    const status = this.statusFilter || undefined;

    this.api.list({ status }).subscribe({
      next: (data) => {
        this.rows = Array.isArray(data) ? data : [];
        this.loading = false;
      },
      error: () => {
        this.errorMsg = 'No se pudieron cargar los incidentes (verificá token admin / endpoint).';
        this.loading = false;
      },
    });
  }

  resolver(row: AdminIncidentRow): void {
    if (!confirm(`¿Marcar incidente #${row.id} como resuelto?`)) return;

    this.api.resolve(row.id).subscribe({
      next: () => this.cargar(),
      error: (err) => {
        alert(err?.error?.error ?? 'No se pudo resolver el incidente.');
      },
    });
  }

  reasignar(row: AdminIncidentRow): void {
    const raw = (this.newUnitIdInput[row.id] ?? '').trim();
    const newUnitId = Number(raw);

    if (!newUnitId || newUnitId <= 0) {
      alert('Ingresá un ID de unidad válido (newUnitId).');
      return;
    }

    if (!confirm(`¿Reasignar incidente #${row.id} a la unidad #${newUnitId}?`)) return;

    this.reassigningId = row.id;

    this.api
      .reassign(row.id, {
        newUnitId,
        markDamagedAsMaintenance: this.markMaintenance[row.id] ?? true,
      })
      .subscribe({
        next: () => {
          this.reassigningId = null;
          this.newUnitIdInput[row.id] = '';
          this.cargar();
        },
        error: (err) => {
          this.reassigningId = null;
          alert(err?.error?.error ?? 'No se pudo reasignar el incidente.');
        },
      });
  }

  badgeClass(status: string): string {
    if (status === 'open') return 'badge badge-open';
    if (status === 'resolved') return 'badge badge-resolved';
    if (status === 'cancelled') return 'badge badge-cancelled';
    return 'badge';
  }
}