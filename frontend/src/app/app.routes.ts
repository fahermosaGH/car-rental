import { Routes } from '@angular/router';
import { LayoutComponent } from './core/layout/layout.component';

export const routes: Routes = [
  { path: '', redirectTo: 'cotizar', pathMatch: 'full' },

  {
    path: '',
    component: LayoutComponent,
    children: [
      // === Auth (/auth/login, /auth/register) ===
      {
        path: 'auth',
        children: [
          {
            path: 'login',
            loadComponent: () =>
              import('./features/auth/login/login.component')
                .then(m => m.LoginComponent),
          },
          {
            path: 'register',
            loadComponent: () =>
              import('./features/auth/register/register.component')
                .then(m => m.RegisterComponent),
          },
        ],
      },

      // === Cotizar ===
      {
        path: 'cotizar',
        children: [
          {
            path: '',
            loadComponent: () =>
              import('./features/cotizar/pages/buscador/buscador.component')
                .then(m => m.BuscadorComponent),
          },
          {
            path: 'resultados',
            loadComponent: () =>
              import('./features/cotizar/pages/resultados/resultados.component')
                .then(m => m.ResultadosComponent),
          },
          {
            path: 'detalle/:id',
            loadComponent: () =>
              import('./features/cotizar/pages/detalle/detalle.component')
                .then(m => m.DetalleComponent),
          },
          {
            path: 'confirmacion/:id',
            loadComponent: () =>
              import('./features/cotizar/pages/confirmacion/confirmacion.component')
                .then(m => m.ConfirmacionComponent),
          },
        ],
      },
    ],
  },

  { path: '**', redirectTo: 'cotizar' },
];



