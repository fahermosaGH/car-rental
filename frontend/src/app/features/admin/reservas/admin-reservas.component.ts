import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import {
  AdminReservasService,
  AdminReservationRow,
  ReservationStatus,
} from '../services/admin-reservas.service';

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

  statuses: ReservationStatus[] = ['pending', 'confirmed', 'completed', 'cancelled'];

  // ===== Modal devolución =====
  showReturnModal = false;
  savingReturn = false;
  returnError = '';

  selectedRow: AdminReservationRow | null = null;
  formReturnNote = '';
  formReturnPenalty: number | null = null;

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

  // ===== Devolución =====
  canRegisterReturn(row: AdminReservationRow): boolean {
    return row.status === 'completed';
  }

  openReturnModal(row: AdminReservationRow): void {
    this.selectedRow = row;
    this.formReturnNote = row.returnNote ?? '';

    // normaliza penalty a number|null (puede venir string del backend)
    const p: any = row.returnPenalty as any;
    this.formReturnPenalty =
      p === null || p === undefined || p === ''
        ? null
        : typeof p === 'number'
          ? p
          : Number(p);

    this.returnError = '';
    this.savingReturn = false;
    this.showReturnModal = true;
  }

  closeReturnModal(): void {
    this.showReturnModal = false;
    this.selectedRow = null;
    this.formReturnNote = '';
    this.formReturnPenalty = null;
    this.returnError = '';
    this.savingReturn = false;
  }

  saveReturn(): void {
    if (!this.selectedRow) return;

    const note = (this.formReturnNote ?? '').trim();
    if (!note) {
      this.returnError = 'La observación es obligatoria.';
      return;
    }

    this.savingReturn = true;
    this.returnError = '';

    this.api
      .updateReturnNote(this.selectedRow.id, {
        returnNote: note,
        returnPenalty: this.formReturnPenalty,
      })
      .subscribe({
        next: (resp) => {
          // actualiza la fila local
          this.selectedRow!.returnNote = resp?.returnNote ?? note;

          const rp = resp?.returnPenalty;
          this.selectedRow!.returnPenalty =
            rp === null || rp === undefined || rp === ''
              ? this.formReturnPenalty
              : typeof rp === 'number'
                ? rp
                : Number(rp);

          this.savingReturn = false;
          this.closeReturnModal();
        },
        error: (err) => {
          const msg =
            err?.error?.error ||
            'No se pudo guardar la observación de devolución.';
          this.returnError = msg;
          this.savingReturn = false;
        },
      });
  }
}