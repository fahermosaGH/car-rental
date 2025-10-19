import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { CotizarService } from '../../services/cotizar.service';
import { VehicleOption } from '../../models/quote';

@Component({
  selector: 'app-resultados',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './resultados.component.html',
  styleUrls: ['./resultados.component.css']
})
export class ResultadosComponent implements OnInit {
  resultados: VehicleOption[] = [];
  dias = 1;
  startAt: string = '';
  endAt: string = '';
  pickupLocationId = 1;
  dropoffLocationId = 1;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cotizarService: CotizarService
  ) {}

  ngOnInit() {
    this.route.queryParamMap.subscribe(params => {
      this.startAt = params.get('startAt') || '';
      this.endAt = params.get('endAt') || '';
      this.pickupLocationId = +(params.get('pickupLocationId') || 1);
      this.dropoffLocationId = +(params.get('dropoffLocationId') || 1);

      if (this.startAt && this.endAt) {
        const diffMs = new Date(this.endAt).getTime() - new Date(this.startAt).getTime();
        this.dias = Math.max(1, Math.ceil(diffMs / (1000 * 60 * 60 * 24)));
      }

      this.cotizarService.buscarVehiculos().subscribe(data => {
        console.log('🚗 Vehículos recibidos:', data);
        this.resultados = data;
      });
    });
  }

  calcularTotal(rate: number) {
    return rate * this.dias;
  }

  verDetalle(id: number) {
    if (!this.startAt || !this.endAt) {
      alert('⚠️ Faltan las fechas de búsqueda. Volvé al buscador.');
      return;
    }

    this.router.navigate(['/cotizar/detalle', id], {
      queryParams: {
        dias: this.dias,
        startAt: this.startAt,
        endAt: this.endAt,
        pickupLocationId: this.pickupLocationId,
        dropoffLocationId: this.dropoffLocationId
      }
    });
  }
}
