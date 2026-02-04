import { Routes } from '@angular/router';
import { LayoutComponent } from './core/layout/layout.component';
import { authGuard } from './core/guards/auth.guard';
import { adminGuard } from './core/guards/admin.guard';
import { LayoutAdminComponent } from './core/layout-admin/layout-admin.component';

import { SobreNosotrosComponent } from './pages/sobre-nosotros/sobre-nosotros.component';
import { AtencionClienteComponent } from './pages/atencion-cliente/atencion-cliente';
import { CentroAyudaComponent } from './pages/centro-ayuda/centro-ayuda';
import { RequisitosAlquilerComponent } from './pages/requisitos-alquiler/requisitos-alquiler';
import { MejorPrecioComponent } from './pages/mejor-precio/mejor-precio';
import { PreguntasFrecuentesComponent } from './pages/preguntas-frecuentes/preguntas-frecuentes';

export const routes: Routes = [
  { path: '', redirectTo: 'cotizar', pathMatch: 'full' },

  // ✅ ADMIN: layout separado + guard ROLE_ADMIN
  {
    path: 'admin',
    component: LayoutAdminComponent,
    canActivate: [adminGuard],
    children: [
      {
        path: '',
        loadComponent: () =>
          import('./features/admin/dashboard/admin-dashboard.component').then(
            (m) => m.AdminDashboardComponent
          ),
      },
      {
        path: 'reservas',
        loadComponent: () =>
          import('./features/admin/reservas/admin-reservas.component').then(
            (m) => m.AdminReservasComponent
          ),
      },
      {
        path: 'vehiculos',
        loadComponent: () =>
          import('./features/admin/vehiculos/admin-vehiculos.component').then(
            (m) => m.AdminVehiculosComponent
          ),
      },
      {
        path: 'ubicaciones',
        loadComponent: () =>
          import('./features/admin/ubicaciones/admin-ubicaciones.component').then(
            (m) => m.AdminUbicacionesComponent
          ),
      },
      {
        path: 'stock',
        loadComponent: () =>
          import('./features/admin/stock/admin-stock.component').then(
            (m) => m.AdminStockComponent
          ),
      },
      {
        path: 'usuarios',
        loadComponent: () =>
          import('./features/admin/usuarios/admin-usuarios.component').then(
            (m) => m.AdminUsuariosComponent
          ),
      },
    ],
  },

  // ✅ USER APP: layout normal
  {
    path: '',
    component: LayoutComponent,
    children: [
      {
        path: 'auth',
        children: [
          {
            path: 'login',
            loadComponent: () =>
              import('./features/auth/login/login.component').then(
                (m) => m.LoginComponent
              ),
          },
          {
            path: 'register',
            loadComponent: () =>
              import('./features/auth/register/register.component').then(
                (m) => m.RegisterComponent
              ),
          },
          {
            path: 'forgot-password',
            loadComponent: () =>
              import(
                './features/auth/forgot-password/forgot-password.component'
              ).then((m) => m.ForgotPasswordComponent),
          },
        ],
      },

      {
        path: 'cotizar',
        children: [
          {
            path: '',
            loadComponent: () =>
              import('./features/cotizar/pages/buscador/buscador.component').then(
                (m) => m.BuscadorComponent
              ),
          },
          {
            path: 'resultados',
            loadComponent: () =>
              import(
                './features/cotizar/pages/resultados/resultados.component'
              ).then((m) => m.ResultadosComponent),
          },
          {
            path: 'detalle/:id',
            loadComponent: () =>
              import('./features/cotizar/pages/detalle/detalle.component').then(
                (m) => m.DetalleComponent
              ),
          },
          {
            path: 'confirmacion/:id',
            loadComponent: () =>
              import(
                './features/cotizar/pages/confirmacion/confirmacion.component'
              ).then((m) => m.ConfirmacionComponent),
          },
        ],
      },

      {
        path: 'flota',
        loadComponent: () =>
          import('./features/flota/ver-flota/ver-flota.component').then(
            (m) => m.VerFlotaComponent
          ),
      },
      {
        path: 'ubicaciones',
        loadComponent: () =>
          import(
            './features/ubicaciones/ver-ubicaciones/ver-ubicaciones.component'
          ).then((m) => m.VerUbicacionesComponent),
      },

      {
        path: 'mis-reservas',
        canActivate: [authGuard],
        loadComponent: () =>
          import(
            './features/reservas/pages/mis-reservas/mis-reservas.component'
          ).then((m) => m.MisReservasComponent),
      },

      {
        path: 'perfil',
        canActivate: [authGuard],
        loadComponent: () =>
          import('./features/cotizar/pages/perfil/perfil.component').then(
            (m) => m.PerfilComponent
          ),
      },

      { path: 'sobre-nosotros', component: SobreNosotrosComponent },
      { path: 'atencion-al-cliente', component: AtencionClienteComponent },
      { path: 'centro-de-ayuda', component: CentroAyudaComponent },
      { path: 'requisitos-alquiler', component: RequisitosAlquilerComponent },
      { path: 'mejor-precio', component: MejorPrecioComponent },
      { path: 'preguntas-frecuentes', component: PreguntasFrecuentesComponent },
    ],
  },

  { path: '**', redirectTo: 'cotizar' },
];