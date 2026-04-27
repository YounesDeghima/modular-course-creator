{{--
    _blockcreate_blade.php
    ──────────────────────
    Headless Livewire component.
    Listens for the  add-block  window event fired by the toolbar,
    creates the block directly (mirrors blockcontroller@store logic),
    then dispatches BlockCreated so the blocks list refreshes.

    Usage: <livewire:modular_site.block.blockcreate :lesson="$lesson" />
--}}
<?php

use App\Models\block;
use Livewire\Component;

new class extends Component {

    public $lesson;
    public $lesson_id;

    public function mount($lesson): void
    {
        $this->lesson    = $lesson;
        $this->lesson_id = $lesson->id;
    }

    /**
     * Entry-point called by the toolbar window event: add-block { type }
     * Type list and content defaults mirror blockcontroller@store exactly.
     */
    public function addBlockOfType(string $type): void
    {
        $allowed = [
            'header','description','note','exercise','code',
            'photo','video','math','graph','table','ext',
            'function','list','separator','markdown',
        ];

        if (! in_array($type, $allowed, true)) {
            return;
        }

        // Place new block after the last existing one
        $blockNumber = (block::where('lesson_id', $this->lesson_id)->max('block_number') ?? 0) + 1;

        $block = block::create([
            'lesson_id'    => $this->lesson_id,
            'type'         => $type,
            'content'      => $this->defaultContent($type),
            'block_number' => $blockNumber,
        ]);

        // Exercise blocks always need a starter solution row (matches controller)
        if ($type === 'exercise') {
            $block->solutions()->create([
                'solution_number' => 1,
                'block_id'        => $block->id,
                'content'         => 'nothing here yet',
            ]);
        }

        $this->dispatch('BlockCreated', id: $block->id);
    }

    /**
     * Mirrors the content-building switch in blockcontroller@store.
     * photo/video stay empty — file uploaded via uploadMedia endpoint.
     */
    private function defaultContent(string $type): string
    {
        return match ($type) {
            'photo', 'video' => '',

            'table' => json_encode([
                ['Column 1', 'Column 2'],
                ['Row 1', 'Data'],
            ]),

            'function' => json_encode([
                'function' => 'sin(x)',
                'x_min'    => -10,
                'x_max'    => 10,
                'y_min'    => -5,
                'y_max'    => 5,
                'color'    => '#4f46e5',
                'step'     => 0.1,
            ]),

            'graph' => json_encode([
                'type'   => 'line',
                'labels' => ['Jan', 'Feb', 'Mar'],
                'data'   => ['10', '20', '15'],
            ]),

            'list' => json_encode([
                'style' => 'bullet',
                'items' => ['Item 1', 'Item 2'],
            ]),

            'separator' => json_encode(['type' => 'divider']),

            'header'      => 'New Heading',
            'description' => 'New paragraph text.',
            'note'        => 'Add your note here.',
            'code'        => '// code here',
            'math'        => '\text{expression}',
            'exercise'    => 'Solve the following...',
            'ext'         => '<p></p>',
            default       => '',
        };
    }
};
?>

<div
    x-data
    x-on:add-block.window="$wire.addBlockOfType($event.detail.type)"
></div>
