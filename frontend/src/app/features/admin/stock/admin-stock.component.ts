import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AdminStockService, StockRowDto } from '../services/admin-stock.service';
import { catchError, finalize, of } from 'rxjs';

@Component({
  selector: 'app-admin-stock',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './admin-stock.component.html',
  styleUrls: ['./admin-stock.component.css'],
})
export class AdminStockComponent implements OnInit {
  rows: StockRowDto[] = [];
  loading = false;
  error = false;

  syncingRow: Record<number, boolean> = {};
  rebuilding = false;

  constructor(private stock: AdminStockService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.error = false;

    this.stock
      .list()
      .pipe(
        catchError(() => {
          this.error = true;
          return of([] as StockRowDto[]);
        }),
        finalize(() => (this.loading = false))
      )
      .subscribe((rows) => {
        this.rows = rows;
      });
  }

  // Sincroniza una fila (vehicle+location) usando el endpoint PUT existente.
  // El backend recalcula quantity desde patentes.
  syncRow(row: StockRowDto): void {
    this.syncingRow[row.id] = true;

    // Mandamos el quantity actual (da igual), el backend lo ignora y recalcula.
    this.stock
      .update(row.id, row.quantity)
      .pipe(finalize(() => (this.syncingRow[row.id] = false)))
      .subscribe({
        next: (res) => {
          // si el backend devuelve { quantity }, lo usamos; si no, recargamos todo
          if (res && typeof res.quantity === 'number') {
            row.quantity = res.quantity;
          } else {
            this.load();
          }
        },
        error: () => {
          alert('No se pudo sincronizar el stock (401 o error de servidor).');
        },
      });
  }

  // Recalcula TODO (botón oro)
  rebuildAll(): void {
    this.rebuilding = true;

    this.stock
      .rebuild()
      .pipe(finalize(() => (this.rebuilding = false)))
      .subscribe({
        next: () => this.load(),
        error: () => alert('No se pudo recalcular el stock.'),
      });
  }
}
