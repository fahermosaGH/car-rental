import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { CotizarService } from '../../services/cotizar.service';
import { VehicleOption } from '../../models/quote';

interface Extra {
  id: string;
  nombre: string;
  precio: number;
  seleccionado: boolean;
}

@Component({
  selector: 'app-confirmacion',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './confirmacion.component.html',
  styleUrls: ['./confirmacion.component.css']
})
export class ConfirmacionComponent implements OnInit {
  vehiculo?: VehicleOption;
  dias: number = 1;
  totalBase: number = 0;
  totalFinal: number = 0;

  cliente = {
    nombre: '',
    email: '',
    edad: null as number | null,
  };

  extras: Extra[] = [
    { id: 'gps', nombre: 'GPS', precio: 4000, seleccionado: false },
    { id: 'babyseat', nombre: 'Silla para bebé', precio: 3500, seleccionado: false },
    { id: 'insurance', nombre: 'Seguro adicional', precio: 8000, seleccionado: false },
  ];

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cotizarService: CotizarService
  ) {}

  ngOnInit() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    this.dias = Number(this.route.snapshot.queryParamMap.get('dias')) || 1;

    this.cotizarService.obtenerVehiculoPorId(id).subscribe(v => {
      if (!v) {
        this.router.navigate(['/cotizar/resultados']);
        return;
      }
      this.vehiculo = v;
      this.totalBase = v.dailyRate * this.dias;
      this.calcularTotal();
    });
  }

  calcularTotal() {
    const extrasSeleccionados = this.extras
      .filter(e => e.seleccionado)
      .reduce((acc, e) => acc + e.precio, 0);
    this.totalFinal = this.totalBase + extrasSeleccionados;
  }

  confirmarReserva() {
    if (!this.cliente.nombre || !this.cliente.email || !this.cliente.edad) {
      alert('⚠️ Por favor completá todos los datos del cliente.');
      return;
    }

    alert(`
✅ Reserva confirmada (simulada)
👤 Cliente: ${this.cliente.nombre}
📧 Email: ${this.cliente.email}
🧾 Total: ARS ${this.totalFinal.toLocaleString()}
`);

    this.router.navigate(['/cotizar']);
  }
}
