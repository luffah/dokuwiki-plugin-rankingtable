
<?php
/**
 * @license GNU Affero General Public License 3 <http://www.gnu.org/licenses/>
 * @author Luffah <contact@luffah.xyz>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

$SORTING_KEY = '_key_';
function sort_by_value($a, $b) {
  global $SORTING_KEY;
  return strnatcmp($a[$SORTING_KEY], $b[$SORTING_KEY]) > 0;
}

class helper_plugin_rankingtable extends DokuWiki_Plugin {

  public function getMethods() {
    $result = array();
    $result[] = array(
      'name' => 'genTableDokuWiki',
      'desc' => 'returns a DokuWiki table in text format',
      'params' => array(
        'datas' => 'array',
        'columns (optionnal)' => 'array'
      ),
      'return' => array('content' => 'text'),
    );
    $result[] = array(
      'name' => 'genTableHTML',
      'desc' => 'returns a table in HTML format',
      'params' => array(
        'datas' => 'array',
        'columns (optionnal)' => 'array'
      ),
      'return' => array('content' => 'text'),
    );
    return $result;
  }

  public function parseDokuWikiTable($content) {
    $rows = array();
    $hdesc = array();
    $vdesc = array();
    $hashed = False;
    $vhashed = False;
    $idx = 0;
    $vkeyidx = -1;
    foreach (explode("\n", $content) as $l){
      $lidx = 0;
      if (strlen($l)==0)
        continue;
      $cells = array_slice(preg_split("/(\^|\|)/", $l, -1, PREG_SPLIT_DELIM_CAPTURE), 1, -1);
      if (count($cells)<2)
        continue;
      $row = array();
      $vkey = Null;
      $nextcellishead = Null;
      foreach ($cells as $cell) {
        if (is_null($nextcellishead)) {
          $nextcellishead = ($cell == "^");
        } else {
          $val = trim($cell);
          if ($nextcellishead) {
            if ($idx == 0) {
              array_push($hdesc, $val);
              $hashed = True;
            } else {
              $vhashed = True;
              array_push($vdesc, $val);
              $vkey = $val;
              $vkeyidx = $lidx;
            }
          } else  {
            if ($hashed) {
              $key = $hdesc[$lidx];
              $row[$key] = $val;
            } else {
              array_push($row, $val);
            }
          }
          $nextcellishead = Null;
          $lidx++;
        }
      }
      if (count($row)>0) {
        if ($vhashed) {
          $rows[$vkey] = $row;
        } else {
          array_push($rows, $row);
        }
      }
      $idx++;
    }
    $desc = Null;
    if ($hashed) {
      $idx = 0;
      foreach($hdesc as $k) {
        if ($idx == $vkeyidx) {
          $desc['_key_'] = $k;
        } else 
          $desc[$k] = $k;
        $idx++;
      }
    }
    return array($rows, $desc, $hdesc, $vdesc);
  }

  private static function _gentable_rows($datas, $desc=Null, $order_by=Null, $reverse=False) {
    $tabrows = array();
    foreach ($datas as $k => $row) {
      $tabrow = array();
      foreach ($desc as $column => $colname) {
        array_push($tabrow, ('_key_' == $column) ? $k : (isset($row[$column])? $row[$column] : ''));
      }
      array_push($tabrows, $tabrow);
    }
    if (!is_null($order_by)) {
      global $SORTING_KEY;
      $SORTING_KEY = array_search($order_by, array_keys($desc));
      usort($tabrows,'sort_by_value');
      if ($reverse) $tabrows = array_reverse($tabrows);
    }
    return $tabrows;
  }
  private static function _gentable_header($datas, $desc=Null) {
    $tabrows = array();
    $heading_col = -1;
    if (is_null($desc)) {  # we have a 2d matrix here
      $heading_col = 0;
      $desc = array('_key_'=>'');
      foreach ($datas as $k => $row) {
        $desc[$k] = $k;
      }
    } else {
      $idx = array_search('_key_', array_keys($desc),True);
      if ($idx !== False) $heading_col = $idx;
    }
    $headingclass ='';
    if (count($desc) > 10) {
      $headingclass = ' class="one-ch"';
    }
    return array($desc, $heading_col, $headingclass);
  }
  private static function _gentable_dokuwiki($datas, $desc=Null, $order_by=Null, $reverse=False) {
    $ret = '';
    list($desc, $heading_col, $headingclass) = self::_gentable_header($datas, $desc);
    foreach ($desc as $column => $colname) {
      $ret .= '^ '. $colname. ' ';
    }
    $ret .= "^\n";
    foreach (self::_gentable_rows($datas, $desc, $order_by, $reverse) as $row) {
      $idx = 0;
      foreach ($row as $cell)
        if ($heading_col == $idx++)
          $ret .= '^ '.$cell.' ';
        else
          $ret .= '| '.$cell.' ';
      $ret .= " |\n";
    }
    return $ret;
  }
  private static function _gentable($datas, $desc=Null, $order_by=Null, $reverse=False) {
    $ret = '<table>';
    $ret .= '<thead><tr>';
    list($desc, $heading_col, $headingclass) = self::_gentable_header($datas, $desc);
    foreach ($desc as $column => $colname) {
      $ret .= '<th '.$headingclass.'><div>'.$colname.'</div></th>';
    }
    $ret .= '</tr></thead>';
    $ret .= '<tbody>';
    foreach (self::_gentable_rows($datas, $desc, $order_by, $reverse) as $row) {
      $ret .= '<tr>';
      $idx = 0;
      foreach ($row as $cell) {
        $rendered = preg_replace(
          array(
            '/\*\*([^*]+)\*\*/',
            '/\/\/([^\/]+)\/\//',
              '/__([^_]+)__/'
          ),
          array(
           '<strong>$1</strong>',
           '<em>$1</em>',
           '<em class="u">$1</em>'
          ), $cell);
        if ($heading_col == $idx++)
          $ret .= '<th>'.$rendered.'</th>';
        else
          $ret .= '<td>'.$rendered.'</td>';
      }
      $ret .= '</tr>';
    }
    $ret .= '</tbody>';
    $ret .= '</table>';
    return $ret;
  }

  public function genTableDokuWiki($datas, $desc=Null, $order_by=Null, $reverse=False){
    return self::_gentable_dokuwiki($datas, $desc, $order_by, $reverse);
  }

  public function genTableHTML($datas, $desc=Null, $order_by=Null, $reverse=False){
    return self::_gentable($datas, $desc, $order_by, $reverse);
  }

}
