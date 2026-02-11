import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import {
  AdminReservasService,
  AdminReservationRow,
  ReservationStatus,
} from '../services/admin-reservas.service'; // ✅ ESTA ES LA RUTA CORRECTA EN TU ESTRUCTURA

@Component({
  selector: 'app-admin-reservas',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-reservas.component.html',
  styleUrls: ['./admin-reservas.component.css'],
})
export class AdminReservasComponent implements OnInit {
  loading = true;
  error = '';
  rows: AdminReservationRow[] = [];

  status: '' | ReservationStatus = '';
  from = '';
  to = '';

  // ✅ Incluimos completed
  statuses: ReservationStatus[] = ['pending', 'confirmed', 'completed', 'cancelled'];

  constructor(private api: AdminReservasService) {}

  ngOnInit(): void {
    this.reload();
  }

  reload(): void {
    this.loading = true;
    this.error = '';

    this.api
      .list({
        status: this.status || undefined,
        from: this.from || undefined,
        to: this.to || undefined,
      })
      .subscribe({
        next: (data) => {
          this.rows = Array.isArray(data) ? data : [];
          this.loading = false;
        },
        error: () => {
          this.error = 'No se pudieron cargar las reservas.';
          this.loading = false;
        },
      });
  }

  changeStatus(row: AdminReservationRow, newStatus: ReservationStatus): void {
    const prev = row.status;
    row.status = newStatus;

    this.api.updateStatus(row.id, newStatus).subscribe({
      next: () => {},
      error: () => {
        row.status = prev;
        this.error = 'No se pudo actualizar el estado.';
      },
    });
  }
}