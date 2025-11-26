import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PreguntasFrecuentesComponent } from './preguntas-frecuentes';

describe('PreguntasFrecuentes', () => {
  let component: PreguntasFrecuentesComponent;
  let fixture: ComponentFixture<PreguntasFrecuentesComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PreguntasFrecuentesComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PreguntasFrecuentesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
