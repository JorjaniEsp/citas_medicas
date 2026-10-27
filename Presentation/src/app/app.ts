import { Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { MedicoComponent } from './components/medico-component/medico-component';

@Component({
  imports: [RouterOutlet, MedicoComponent],
  selector: 'app-root',
  styleUrl: './app.css',
  templateUrl: './app.html',
})
export class App {
  protected readonly title = signal('citas_medicas');
}
