<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Enfermeiro;
use App\Models\Usuario;
use App\Models\Unidade;
use App\Mail\emailEnfermeiro;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; // Importado para debug

class EnfermeiroController extends Controller
{
    // Listagem de enfermeiros
    public function index()
    {
        $enfermeiros = Enfermeiro::with('usuario')->get();
        return view('admin.manutencaoEnfermeiro', compact('enfermeiros'));
    }

    /**
     * AJUSTADO: Mostra o formulário de cadastro E envia a lista de unidades.
     */
    public function create()
    {
        // Busca todas as unidades para listarmos no formulário de seleção
        $unidades = Unidade::orderBy('nomeUnidade')->get();
        return view('admin.cadastroEnfermeiro', compact('unidades'));
    }

    /**
     * AJUSTADO: Salva o novo enfermeiro E as suas unidades de trabalho.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nomeEnfermeiro' => 'required|string|max:255',
            'emailEnfermeiro' => [
                'required',
                'email',
                Rule::unique((new Enfermeiro)->getTable(), 'emailEnfermeiro'),
            ],
            'corenEnfermeiro' => 'required|string|max:50',
            'especialidadeEnfermeiro' => 'nullable|string|max:100',
            'genero' => 'required|string|max:20',
            'unidades' => 'nullable|array', // Valida que 'unidades' é uma lista (se enviada)
            'unidades.*' => 'exists:tbUnidade,idUnidadePK', // Valida cada ID da lista
        ]);

        $senhaTemporaria = Str::random(10);

        $usuario = Usuario::create([
            'nomeUsuario' => $request->nomeEnfermeiro,
            'emailUsuario' => $request->emailEnfermeiro,
            'senhaUsuario' => Hash::make($senhaTemporaria),
            'statusAtivoUsuario' => true,
            'statusSenhaUsuario' => true,
        ]);

        $enfermeiro = Enfermeiro::create([
            'nomeEnfermeiro' => $request->nomeEnfermeiro,
            'emailEnfermeiro' => $request->emailEnfermeiro,
            'corenEnfermeiro' => $request->corenEnfermeiro,
            'especialidadeEnfermeiro' => $request->especialidadeEnfermeiro,
            'genero' => $request->genero,
            'id_usuario' => $usuario->idUsuarioPK,
        ]);

        // Se o admin selecionou unidades no formulário, associa-as ao enfermeiro
        if ($request->has('unidades')) {
            $enfermeiro->unidades()->sync($request->unidades);
        }

        Mail::to($request->emailEnfermeiro)->send(new emailEnfermeiro($usuario, $senhaTemporaria));


        return response()->json(['message' => 'Enfermeiro pré-cadastrado com sucesso!']);
    }

    public function editar($id)
    {
        $enfermeiro = Enfermeiro::with('usuario')->findOrFail($id);
        return view('admin.editarEnfermeiro', compact('enfermeiro'));
    }

    // MÉTODO UPDATE (EDITADO PARA MODAL DE SUCESSO)
    public function update(Request $request, $id)
    {
        $enfermeiro = Enfermeiro::with('usuario')->findOrFail($id);

        $request->validate([
            'nomeEnfermeiro' => 'required|string|max:255',
            'emailEnfermeiro' => [
                'required',
                'email',
                Rule::unique((new Enfermeiro)->getTable(), 'emailEnfermeiro')->ignore($id, 'idEnfermeiroPK'),
            ],
            'corenEnfermeiro' => 'required|string|max:50',
            'especialidadeEnfermeiro' => 'nullable|string|max:100',
            'genero' => 'required|string|max:20',

            'nomeUsuario' => 'required|string|max:255',
            'emailUsuario' => [
                'required',
                'email',
                Rule::unique((new Usuario)->getTable(), 'emailUsuario')
                    ->ignore($enfermeiro->usuario->idUsuarioPK, 'idUsuarioPK'),
            ],
        ]);

        $enfermeiro->update([
            'nomeEnfermeiro' => $request->nomeEnfermeiro,
            'emailEnfermeiro' => $request->emailEnfermeiro,
            'corenEnfermeiro' => $request->corenEnfermeiro,
            'especialidadeEnfermeiro' => $request->especialidadeEnfermeiro,
            'genero' => $request->genero,
        ]);

        $enfermeiro->usuario->update([
            'nomeUsuario' => $request->nomeUsuario,
            'emailUsuario' => $request->emailUsuario,
        ]);

        // ADICIONANDO A FLAG 'updated' PARA DISPARAR O MODAL DE SUCESSO NO BLADE
        return redirect()->route('admin.manutencaoEnfermeiro')->with([
            'success' => 'Dados atualizados com sucesso.',
            'updated' => true // Flag para edição/atualização
        ]);
    }

    public function show($id)
    {
        $enfermeiro = Enfermeiro::with('usuario')->findOrFail($id);
        return view('admin.visualizarEnfermeiro', compact('enfermeiro'));
    }

    // MÉTODO TOGGLESTATUS (EDITADO PARA MODAL DE SUCESSO)
    public function toggleStatus($id)
    {
        $enfermeiro = Enfermeiro::with('usuario')->findOrFail($id);
        $mensagem = 'Status do enfermeiro atualizado.'; // Mensagem padrão

        if ($enfermeiro->usuario) {
            // Inverte o status
            $novoStatus = !$enfermeiro->usuario->statusAtivoUsuario;
            $enfermeiro->usuario->statusAtivoUsuario = $novoStatus;
            $enfermeiro->usuario->save();

            // Mensagem mais específica para o modal
            $acao = $novoStatus ? 'ativado' : 'desativado';
            $mensagem = "O enfermeiro(a) foi {$acao} com sucesso!";
        }

        // ADICIONANDO A FLAG 'status_changed' PARA DISPARAR O MODAL DE SUCESSO NO BLADE
        return redirect()->route('admin.manutencaoEnfermeiro')->with([
            'success' => $mensagem,
            'status_changed' => true // Flag para alteração de status
        ]);
    }

    // REMOVEMOS A FUNÇÃO 'confirmarExclusao' E 'excluir' PARA ADOTAR A LÓGICA DE INATIVAR
    // O código original ainda tinha essas funções, agora elas serão removidas para seguir a nova lógica.
    
    /*
    public function confirmarExclusao($id)
    {
        $enfermeiro = Enfermeiro::findOrFail($id);
        return view('admin.desativarEnfermeiro', compact('enfermeiro'));
    }

    public function excluir($id)
    {
        $enfermeiro = Enfermeiro::with('usuario')->findOrFail($id);
        if ($enfermeiro->usuario) {
            $enfermeiro->usuario->delete();
        }
        $enfermeiro->delete();
        return redirect()->route('admin.manutencaoEnfermeiro')->with([
            'success' => 'Enfermeiro e usuário excluídos com sucesso.',
            'deleted' => true 
        ]);
    }
    */

    public function syncUnidades(Request $request, Enfermeiro $enfermeiro)
    {
        $request->validate([
            'unidades' => 'required|array',
            'unidades.*' => 'exists:tbUnidade,idUnidadePK',
        ]);

        $enfermeiro->unidades()->sync($request->unidades);

        return response()->json([
            'message' => 'Unidades do enfermeiro atualizadas com sucesso!',
            'enfermeiro' => $enfermeiro->load('unidades')
        ]);
    }
}