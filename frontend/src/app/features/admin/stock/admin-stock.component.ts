import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AdminStockService, StockRowDto } from '../services/admin-stock.service';
import { catchError, finalize, of } from 'rxjs';

@Component({
  selector: 'app-admin-stock',
  standalone: true,
  imports: [CommonModule],
  template: `
  <div class="head">
    <div>
      <h2>Stock</h2>
      <p class="sub">Stock por vehículo y sucursal (editar cantidad).</p>
    </div>
    <button class="btn" type="button" (click)="load()" [disabled]="loading">Recargar</button>
  </div>

  <div *ngIf="error" class="alert">
    No se pudo cargar el stock.
    <button class="btn-small" type="button" (click)="load()">Reintentar</button>
  </div>

  <div class="card" *ngIf="!loading && rows.length === 0 && !error">
    No hay stock cargado.
  </div>

  <div class="table-wrap" *ngIf="rows.length > 0">
    <table class="table">
      <thead>
        <tr>
          <th>Sucursal</th>
          <th>Vehículo</th>
          <th class="num">Cantidad</th>
          <th class="num">Acciones</th>
        </tr>
      </thead>

      <tbody>
        <tr *ngFor="let r of rows">
          <td>
            <div class="strong">{{ r.location?.name }}</div>
            <div class="muted">{{ r.location?.city ?? '-' }}</div>
          </td>

          <td>
            <div class="strong">
              {{ r.vehicle?.brand ?? '' }} {{ r.vehicle?.model ?? '' }}
            </div>
            <div class="muted">ID: {{ r.vehicle?.id }} · {{ r.vehicle?.year ?? '-' }}</div>
          </td>

          <td class="num">
            <input
              class="qty"
              type="number"
              [value]="draftQty[r.id] ?? r.quantity"
              (input)="onQtyChange(r.id, $any($event.target).value)"
              min="0"
            />
          </td>

          <td class="num">
            <button class="btn-small" type="button" (click)="save(r)" [disabled]="saving[r.id]">
              Guardar
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  `,
  styles: [`
    .head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px}
    h2{margin:0 0 6px 0}
    .sub{margin:0;opacity:.8}
    .alert{border:1px solid rgba(255,80,80,.35);background:rgba(255,80,80,.06);padding:12px;border-radius:12px;display:flex;gap:10px;align-items:center}
    .card{border:1px solid rgba(255,255,255,.08);background:rgba(10,15,28,.4);padding:14px;border-radius:12px}
    .table-wrap{border:1px solid rgba(255,255,255,.08);border-radius:14px;overflow:hidden}
    .table{width:100%;border-collapse:collapse}
    th,td{padding:12px 12px;border-bottom:1px solid rgba(255,255,255,.06);text-align:left}
    th{font-size:12px;opacity:.8}
    .num{text-align:right}
    .strong{font-weight:700}
    .muted{opacity:.75;font-size:12px;margin-top:2px}
    .btn{border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.06);color:#e8eefc;padding:10px 12px;border-radius:10px;cursor:pointer}
    .btn:disabled{opacity:.5;cursor:not-allowed}
    .btn-small{border:1px solid rgba(255,255,255,.16);background:transparent;color:#e8eefc;padding:8px 10px;border-radius:10px;cursor:pointer}
    .qty{width:110px;text-align:right;border-radius:10px;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.04);color:#e8eefc;padding:8px 10px}
  `]
})
export class AdminStockComponent implements OnInit {
  rows: StockRowDto[] = [];
  loading = false;
  error = false;

  // draft por fila
  draftQty: Record<number, number> = {};
  saving: Record<number, boolean> = {};

  constructor(private stock: AdminStockService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.error = false;

    this.stock.list()
      .pipe(
        catchError(() => {
          this.error = true;
          return of([] as StockRowDto[]);
        }),
        finalize(() => (this.loading = false))
      )
      .subscribe((rows) => {
        this.rows = rows;
        // inicializa drafts con valor actual
        rows.forEach(r => {
          this.draftQty[r.id] = r.quantity;
        });
      });
  }

  onQtyChange(rowId: number, value: any): void {
    const n = Number(value);
    this.draftQty[rowId] = Number.isFinite(n) ? n : 0;
  }

  save(row: StockRowDto): void {
    const qty = Math.max(0, Number(this.draftQty[row.id] ?? row.quantity));

    this.saving[row.id] = true;
    this.stock.update(row.id, qty)
      .pipe(finalize(() => (this.saving[row.id] = false)))
      .subscribe({
        next: () => {
          row.quantity = qty;
        },
        error: () => {
          alert('No se pudo guardar el stock (401 o error de servidor).');
        }
      });
  }
}