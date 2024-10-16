<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistantAi extends Model
{
    use HasFactory;

    protected $table = "assistants_ai";
    public $timestamps = false;

    protected $fillable = [
        'assistant_id',
        'vector_store_id',
        'active'
    ];

     /**
     * Define o estado ativo do assistente.
     *
     * @param bool $state
     * @return void
     */
    public function setActive(bool $state): void
    {
        $this->active = $state ? 1 : 0;
        $this->save();
    }

    /**
     * Obtém um assistente disponível.
     *
     * @return self
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function getAvailableAssistant(): self
    {
        return self::where('active', 0)->firstOrFail();
    }

    /**
     * Obtém um assistente gerador de Tags.
     *
     * @return self
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function getAvailableAssistantGenerateTags(): self
    {
        return self::find(5);
    }
}
