import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-admin-usuarios',
  standalone: true,
  imports: [CommonModule],
  template: `
    <h2>Usuarios</h2>
    <p>Listado de clientes registrados + detalle + historial + asignación de roles.</p>
  `,
})
export class AdminUsuariosComponent {}
