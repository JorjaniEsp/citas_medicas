import { inject, Injectable, Service, signal } from '@angular/core';
import { MedicoModel } from '../models/medico-model';
import { HttpClient, httpResource } from '@angular/common/http';
import { environment } from '../../../environments/environment.development';

type ParametroMedico = Record<string, string | number | boolean>;

interface RespuestaMedicos {
    data: MedicoModel[];
    pagination: {
        offset: number;
        limit: number;
        total: number;
    };
}

interface RespuestaMedico {
    data: MedicoModel | null;
}

const _SERVIDOR = environment.servidor;

//@Service()
@Injectable({ providedIn: 'root' })
export class MedicoService {

    private readonly http = inject(HttpClient);
    private readonly medicoId = signal< number | null>(null);

    private readonly parametros = signal<ParametroMedico | null>(null);
    readonly medicos = httpResource<RespuestaMedicos>(()=>{
        const parametros = this.parametros();

        if (parametros === null) {
            return undefined;
        }

        return {
            url     : `${_SERVIDOR}/medicos/filter/0/10`,
            params  : parametros
        };
    });

    readonly medicoPorId = httpResource<RespuestaMedico>(() => {
        const id = this.medicoId();
        return id === null ? undefined : `${_SERVIDOR}/medicos/${id}`;
    });

    public filtrar(parametros: ParametroMedico): void {
        this.parametros.set(parametros);
    }

    public buscarPorId(id:number) : void {
        this.medicoId.set(id);
    }

    public guardar(datos: MedicoModel, id?: number) {
        delete datos.id;

        if (id) {
            return this.http.put<any>(`${_SERVIDOR}/medicos/${id}`, datos);
        } else {
            return this.http.post<any>(`${_SERVIDOR}/medicos`, datos);
        }
    }

}
