<?php

declare(strict_types=1);

namespace App\Domain\CreationIdeas\Services;

use App\Domain\CreationIdeas\DTOs\CreationIdeaContext;
use Illuminate\Support\Str;

/**
 * Deterministic Portuguese templates keyed by cycle context.
 */
final readonly class CreationIdeaCopyGenerator
{
    /**
     * @return array{headline: string, suggestion: string}
     */
    public function generate(string $target, CreationIdeaContext $context): array
    {
        $busy = $context->task_count_on_day > 0;
        $copy = $this->specific($target, $context, $busy) ?? $this->generic($target, $context, $busy);

        return [
            'headline' => Str::limit($copy['headline'], 255, ''),
            'suggestion' => $copy['suggestion'],
        ];
    }

    /**
     * @return array{headline: string, suggestion: string}|null
     */
    private function specific(string $target, CreationIdeaContext $context, bool $busy): ?array
    {
        $key = implode('|', [
            $target,
            $context->season,
            $context->day_period,
            $context->weekday,
            $busy ? 'busy' : 'empty',
        ]);

        return match ($key) {
            'project|spring|night|sunday|empty' => [
                'headline' => 'Protótipo de uma ideia ainda não explorada',
                'suggestion' => 'Use este momento para crescimento e imaginação. Crie um projeto em torno de uma ideia que você ainda não testou, com o objetivo de validar uma versão pequena na semana que começa.',
            ],
            'project|winter|morning|monday|empty' => [
                'headline' => 'Prioridades da semana que começa',
                'suggestion' => 'Use este momento para planejamento. Organize um projeto em torno das prioridades da semana, definindo o que precisa ser decidido antes de agir.',
            ],
            'task|summer|afternoon|wednesday|empty' => [
                'headline' => 'Próxima ação de maior impacto',
                'suggestion' => 'Use este momento para execução. Avance agora a tarefa de maior impacto deste projeto, enquanto o ritmo da tarde ainda favorece progresso concreto.',
            ],
            'project|autumn|afternoon|friday|empty' => [
                'headline' => 'Revisão e encerramento da semana',
                'suggestion' => 'Use este momento para reflexão. Feche o que já está em andamento e revise o que pode ser concluído, em vez de abrir um novo ciclo de execução.',
            ],
            'project|autumn|afternoon|friday|busy' => [
                'headline' => 'Revisão do que já está na agenda',
                'suggestion' => 'Sua sexta já está ocupada. Use este momento de outono para revisar e encerrar o que já está no calendário, em vez de adicionar mais execução.',
            ],
            default => null,
        };
    }

    /**
     * @return array{headline: string, suggestion: string}
     */
    private function generic(string $target, CreationIdeaContext $context, bool $busy): array
    {
        $seasonVerb = match ($context->primary_mode) {
            'growth' => 'crescimento',
            'action' => 'ação',
            'reflection' => 'reflexão',
            default => 'planejamento',
        };

        $periodVerb = match ($context->secondary_mode) {
            'execution' => 'execução',
            'imagination' => 'imaginação e reflexão',
            default => 'planejamento e prioridade',
        };

        if ($target === 'task') {
            if ($busy) {
                return [
                    'headline' => 'Próximo passo alinhado à agenda',
                    'suggestion' => "Use este momento para {$periodVerb}. Avance uma tarefa deste projeto que já dialoga com o que está no calendário, sem empilhar mais execução do que o dia comporta.",
                ];
            }

            return [
                'headline' => 'Próxima tarefa alinhada ao ciclo',
                'suggestion' => "Use este momento para {$seasonVerb} e {$periodVerb}. Crie uma tarefa concreta neste projeto, com um resultado pequeno o suficiente para concluir neste ciclo.",
            ];
        }

        if ($busy) {
            return [
                'headline' => 'Projeto de revisão do que já está em curso',
                'suggestion' => "Sua agenda neste dia já tem tarefas. Use este momento para {$seasonVerb} e {$periodVerb}, organizando um projeto que revise ou feche o que já existe em vez de abrir mais frentes.",
            ];
        }

        return [
            'headline' => 'Projeto alinhado ao ciclo atual',
            'suggestion' => "Use este momento para {$seasonVerb} e {$periodVerb}. Crie um projeto em torno de um objetivo pequeno o suficiente para avançar nesta semana.",
        ];
    }
}
