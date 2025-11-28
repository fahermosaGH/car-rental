import { Routes } from '@angular/router';
import { LayoutComponent } from './core/layout/layout.component';
import { authGuard } from './core/guards/auth.guard';

import { SobreNosotrosComponent } from './pages/sobre-nosotros/sobre-nosotros.component';
import { AtencionClienteComponent } from './pages/atencion-cliente/atencion-cliente';
import { LoginComponent } from './features/auth/login/login.component';
import { MisReservasComponent } from './features/reservas/pages/mis-reservas/mis-reservas.component';
import { CentroAyudaComponent } from './pages/centro-ayuda/centro-ayuda';
import { RequisitosAlquilerComponent } from './pages/requisitos-alquiler/requisitos-alquiler';
import { MejorPrecioComponent } from './pages/mejor-precio/mejor-precio';

// 👉 NUEVO IMPORT
import { PreguntasFrecuentesComponent } from './pages/preguntas-frecuentes/preguntas-frecuentes';

export const routes: Routes = [
  { path: '', redirectTo: 'cotizar', pathMatch: 'full' },

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
              import('./features/auth/login/login.component').then((m) => m.LoginComponent),
          },
          {
            path: 'register',
            loadComponent: () =>
              import('./features/auth/register/register.component').then((m) => m.RegisterComponent),
          },
        ],
      },

      {
        path: 'cotizar',
        children: [
          {
            path: '',
            loadComponent: () =>
              import('./features/cotizar/pages/buscador/buscador.component').then((m) => m.BuscadorComponent),
          },
          {
            path: 'resultados',
            loadComponent: () =>
              import('./features/cotizar/pages/resultados/resultados.component').then((m) => m.ResultadosComponent),
          },
          {
            path: 'detalle/:id',
            loadComponent: () =>
              import('./features/cotizar/pages/detalle/detalle.component').then((m) => m.DetalleComponent),
          },
          {
            path: 'confirmacion/:id',
            loadComponent: () =>
              import('./features/cotizar/pages/confirmacion/confirmacion.component').then((m) => m.ConfirmacionComponent),
          },
        ],
      },

      // 🔹 NUEVAS RUTAS PARA VER FLOTA Y VER UBICACIONES
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
          import('./features/ubicaciones/ver-ubicaciones/ver-ubicaciones.component').then(
            (m) => m.VerUbicacionesComponent
          ),
      },

      {
        path: 'mis-reservas',
        canActivate: [authGuard],
        loadComponent: () =>
          import('./features/reservas/pages/mis-reservas/mis-reservas.component').then((m) => m.MisReservasComponent),
      },

      { path: 'sobre-nosotros', component: SobreNosotrosComponent },
      { path: 'atencion-al-cliente', component: AtencionClienteComponent },
      { path: 'login', component: LoginComponent },
      { path: 'centro-de-ayuda', component: CentroAyudaComponent },
      { path: 'requisitos-alquiler', component: RequisitosAlquilerComponent },
      { path: 'mejor-precio', component: MejorPrecioComponent },

      // 👉 NUEVA RUTA COMPLETA
      {
        path: 'preguntas-frecuentes',
        component: PreguntasFrecuentesComponent,
      },
    ],
  },

  { path: '**', redirectTo: 'cotizar' },
];

