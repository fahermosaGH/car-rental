import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterModule, Router } from '@angular/router';
import { CotizarService } from '../../services/cotizar.service';
import { VehicleOption } from '../../models/quote';

@Component({
  selector: 'app-detalle',
  standalone: true,
  imports: [CommonModule, RouterModule], // ✅ IMPORTANTE: agregamos RouterModule
  templateUrl: './detalle.component.html',
  styleUrls: ['./detalle.component.css']
})
export class DetalleComponent implements OnInit {
  vehiculo?: VehicleOption;
  dias: number = 1;
  total: number = 0;

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
      this.total = v.dailyRate * this.dias;
    });
  }
}
