<?php
/**
 * @license GNU Affero General Public License 3 <http://www.gnu.org/licenses/>
 * @author Luffah <contact@luffah.xyz>
 */

if(!defined('DOKU_INC')) die();

class syntax_plugin_rankingtable extends DokuWiki_Syntax_Plugin {
  function getAllowedTypes() { return array('substitution','protected','disabled','formatting'); }
  public function getType(){ return 'container'; }
  public function getPType(){ return 'block'; }
  public function getSort(){ return 50; }

  public function connectTo($mode) {
    $this->Lexer->addEntryPattern('<rankingtable\b.*?', $mode, 'plugin_rankingtable');
  }

  public function postConnect() {
    $this->Lexer->addExitPattern('</rankingtable>', 'plugin_rankingtable');
  }

  public function handle($match, $state, $pos, Doku_Handler $handler){
    $data = array();
    switch ($state) {
    case DOKU_LEXER_ENTER : 
      break;
    case DOKU_LEXER_MATCHED :
      break;
    case DOKU_LEXER_UNMATCHED :
      list($params, $content) = explode('>', $match, 2);
      $pkeys = preg_split("/order by (.*) (asc|desc)/", $params, -1, PREG_SPLIT_DELIM_CAPTURE);
      $keys = array();
      foreach ($pkeys as $p) {
        $k = trim($p);
        if (!empty($k))
          array_push($keys, $k);
      }
      $data['orderby'] = isset($keys[0]) ? $keys[0] : '_key_';
      $data['reverse'] = isset($keys[1]) ? ($keys[1] == 'desc') : false;
      $data['content'] = $content;
      break;
    case DOKU_LEXER_EXIT :
      break;
    case DOKU_LEXER_SPECIAL :
      break;
    }
    return $data;
  }

  public function render($mode, Doku_Renderer $renderer, $data) {

    if($mode == 'xhtml'){
      if (!isset($data['content'])) return false;
/*      $renderer->info['cache'] = false;*/
      $tabletool = $this->loadHelper('rankingtable'); // or $this->loadHelper('tag', true);
      list($t, $desc, $hkeys, $vkeys) = $tabletool->parseDokuWikiTable($data['content']);
      $renderer->doc .= $tabletool->genTableHTML($t, $desc, $data['orderby'], $data['reverse']);
      

      return true;
    }
    return false;

  }
}
