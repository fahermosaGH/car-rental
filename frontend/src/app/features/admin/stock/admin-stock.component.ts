import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-admin-stock',
  standalone: true,
  imports: [CommonModule],
  template: `
    <h2>Stock</h2>
    <p>Stock por sucursal + movimientos + reasignaciones (mover unidades entre sucursales).</p>
  `,
})
export class AdminStockComponent {}
