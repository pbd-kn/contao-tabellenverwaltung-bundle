<?php
declare(strict_types=1);
namespace PBDKN\TabellenverwaltungBundle\Backend;

use Contao\KeyValueWizard;

/** Keep Contao's row controls and submitted field names; expand only the value editor. */
final class EntryValueWizard extends KeyValueWizard
{
    public function generate()
    {
        $html=parent::generate();
        $html=str_replace('class="tl_key_value_wizard"','class="tl_key_value_wizard pbd-entry-values"',$html);
        $html=preg_replace_callback('/<input\b[^>]*\bname="[^"]*\[value\]"[^>]*>/',static function(array $match):string {
            preg_match('/\bvalue="([^"]*)"/',$match[0],$value);
            $attributes='';
            foreach (['disabled','readonly'] as $attribute) {
                if (preg_match('/\s'.$attribute.'(?:\s|=|>)/',$match[0])) { $attributes.=' '.$attribute; }
            }
            return str_replace('type="text"','type="hidden"',$match[0])
                .'<textarea class="tl_text pbd-entry-value" rows="2" aria-label="Wert"'.$attributes.'>'.($value[1]??'').'</textarea>';
        },$html);
        $id=json_encode('ctrl_'.$this->strId,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
        return $html . '<style>
table.pbd-entry-values { width:100%; max-width:none; table-layout:fixed; }
table.pbd-entry-values th:first-child { width:22%; }
table.pbd-entry-values th:last-child { width:88px; }
table.pbd-entry-values td { vertical-align:top; padding-bottom:8px; }
table.pbd-entry-values td input.tl_text, table.pbd-entry-values td textarea.pbd-entry-value { width:100%; max-width:none; box-sizing:border-box; }
table.pbd-entry-values td textarea.pbd-entry-value { min-height:56px; resize:vertical; overflow-wrap:anywhere; white-space:pre-wrap; font:inherit; line-height:1.5; }
@media(max-width:600px) { table.pbd-entry-values th:first-child { width:28%; } table.pbd-entry-values th:last-child { width:70px; } }
</style><script>
(function () {
    const table=document.getElementById('.$id.');
    if (!table) return;
    function resize(editor) {
        editor.style.height="auto";
        editor.style.height=Math.max(56,editor.scrollHeight+2)+"px";
    }
    function refresh() {
        table.querySelectorAll("textarea.pbd-entry-value").forEach(function(editor) {
            const input=editor.parentElement.querySelector("input[type=hidden]");
            editor.value=input.value;
            const key=editor.closest("tr").querySelector("input[type=text]");
            editor.setAttribute("aria-label","Wert für "+(key ? key.value : "Feld"));
            resize(editor);
        });
    }
    table.addEventListener("input",function(event) {
        const editor=event.target;
        if (!editor.matches("textarea.pbd-entry-value")) return;
        editor.parentElement.querySelector("input[type=hidden]").value=editor.value;
        resize(editor);
    });
    new MutationObserver(refresh).observe(table.querySelector("tbody"),{childList:true});
    refresh();
})();
</script>';
    }
}
