import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AdminUsersService, AdminUserRow } from './admin-users.service';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-admin-usuarios',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-usuarios.component.html',
  styleUrls: ['./admin-usuarios.component.css'],
})
export class AdminUsuariosComponent implements OnInit {
  loading = true;
  error = '';
  rows: AdminUserRow[] = [];

  showInactive = false;

  constructor(private api: AdminUsersService) {}

  ngOnInit(): void {
    this.cargar();
  }

  cargar(): void {
    this.loading = true;
    this.error = '';

    this.api.list(this.showInactive).subscribe({
      next: (data) => {
        this.rows = data;
        this.loading = false;
      },
      error: () => {
        this.error = 'No se pudieron cargar los usuarios.';
        this.loading = false;
      },
    });
  }

  toggleShowInactive(): void {
    this.showInactive = !this.showInactive;
    this.cargar();
  }

  onRoleChange(row: AdminUserRow, role: 'ROLE_ADMIN', checked: boolean): void {
    const roles = new Set(row.roles);

    if (checked) roles.add(role);
    else roles.delete(role);

    // aseguramos ROLE_USER mínimo
    roles.add('ROLE_USER');

    const newRoles = Array.from(roles);
    this.api.updateRoles(row.id, newRoles).subscribe({
      next: () => (row.roles = newRoles),
      error: () => (this.error = 'No se pudieron actualizar los roles.'),
    });
  }

  toggleActive(row: AdminUserRow): void {
    const next = !row.isActive;

    this.api.setActive(row.id, next).subscribe({
      next: () => (row.isActive = next),
      error: () => (this.error = 'No se pudo actualizar el estado del usuario.'),
    });
  }

  hasRole(row: AdminUserRow, role: string): boolean {
    return row.roles.includes(role);
  }
}
