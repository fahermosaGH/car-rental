import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AdminUsersService, AdminUserRow } from './admin-users.service';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';

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

  constructor(private api: AdminUsersService, private auth: AuthService) {}

  ngOnInit(): void {
    this.cargar();
  }

  private get myEmail(): string | null {
    return this.auth.currentUser?.email ?? null;
  }

  isMe(row: AdminUserRow): boolean {
    return !!this.myEmail && row.email === this.myEmail;
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

  // ✅ Confirmación fuerte (en vez de “alert”) cuando intenta quitarse admin
  private confirmSelfRemoveAdmin(): boolean {
    return window.confirm(
      'Estás por quitarte el rol de Administrador.\n\n' +
        '• Vas a perder acceso al panel /admin.\n' +
        '• Si no existe otro admin, podrías quedarte sin forma de recuperarlo.\n\n' +
        '¿Querés continuar?'
    );
  }

  // ✅ Confirmación fuerte (opcional) cuando intenta desactivarse
  private confirmSelfDeactivate(): boolean {
    return window.confirm(
      'Estás por desactivar tu propia cuenta.\n\n' +
        '• Se cerrará tu sesión.\n' +
        '• No vas a poder volver a iniciar sesión hasta que otro admin te active.\n\n' +
        '¿Querés continuar?'
    );
  }

  onRoleAdminToggle(row: AdminUserRow, checked: boolean): void {
    // Queremos detectar específicamente: "me estoy sacando admin"
    const isRemovingAdmin = this.isMe(row) && !checked && row.roles.includes('ROLE_ADMIN');

    if (isRemovingAdmin) {
      const ok = this.confirmSelfRemoveAdmin();

      // Si cancela: NO hacemos request y dejamos el checkbox como estaba
      if (!ok) {
        // fuerza a que la UI refleje el estado real (sigue siendo admin)
        // (como el HTML usa [checked]="hasRole(...)", esto ya vuelve solo al re-render;
        // pero recargar filas asegura que no quede raro si el navegador dejó el estado visual)
        this.cargar();
        return;
      }

      // Si confirmó: igualmente lo bloqueamos por seguridad en UI (tu regla original)
      // Si preferís permitirlo con confirmación, comentá el return y dejá que siga.
      alert('Por seguridad, no podés quitarte ROLE_ADMIN desde el panel. Pedile a otro admin que lo haga.');
      this.cargar();
      return;
    }

    const roles = new Set(row.roles);

    if (checked) roles.add('ROLE_ADMIN');
    else roles.delete('ROLE_ADMIN');

    roles.add('ROLE_USER'); // mínimo

    const newRoles = Array.from(roles);

    this.api.updateRoles(row.id, newRoles).subscribe({
      next: () => (row.roles = newRoles),
      error: (e) => {
        this.error =
          e?.status === 409
            ? (e?.error?.error ?? 'Operación no permitida.')
            : 'No se pudieron actualizar los roles.';
        this.cargar();
      },
    });
  }

  toggleActive(row: AdminUserRow): void {
    const next = !row.isActive;

    // 🔥 UI protección: no permitir auto-desactivarse (con confirmación)
    if (this.isMe(row) && next === false) {
      const ok = this.confirmSelfDeactivate();
      if (!ok) {
        this.cargar();
        return;
      }

      // Si confirmó: por seguridad mantenemos tu regla original de NO permitir.
      // Si querés permitirlo con confirmación, comentá este bloque y dejá seguir al request.
      alert('Por seguridad, no podés desactivarte a vos mismo desde el panel. Pedile a otro admin que lo haga.');
      this.cargar();
      return;
    }

    this.api.setActive(row.id, next).subscribe({
      next: () => (row.isActive = next),
      error: (e) => {
        this.error =
          e?.status === 409
            ? (e?.error?.error ?? 'Operación no permitida.')
            : 'No se pudo actualizar el estado del usuario.';
        this.cargar();
      },
    });
  }

  hasRole(row: AdminUserRow, role: string): boolean {
    return row.roles.includes(role);
  }
}


