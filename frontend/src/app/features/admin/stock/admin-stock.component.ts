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
  rebuilding = false;
  error = false;

  syncingRow: Record<number, boolean> = {};

  constructor(private api: AdminStockService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.error = false;

    this.api
      .list()
      .pipe(
        catchError(() => {
          this.error = true;
          return of([] as StockRowDto[]);
        }),
        finalize(() => (this.loading = false))
      )
      .subscribe((rows) => (this.rows = rows ?? []));
  }

  rebuildAll(): void {
    this.rebuilding = true;

    this.api
      .rebuildAll()
      .pipe(finalize(() => (this.rebuilding = false)))
      .subscribe({
        next: () => this.load(),
        error: () => alert('No se pudo recalcular el stock (401 o error de servidor).'),
      });
  }

  syncRow(row: StockRowDto): void {
    this.syncingRow[row.id] = true;

    this.api
      .syncRow(row)
      .pipe(finalize(() => (this.syncingRow[row.id] = false)))
      .subscribe({
        next: () => this.load(),
        error: () => alert('No se pudo sincronizar el stock (401 o error de servidor).'),
      });
  }
}