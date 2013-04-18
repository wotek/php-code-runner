<?php

class SnippetsDAO {
  protected $_pdo;

  public function __construct() {
    $this->_pdo = new SQLite3('db/cr.db', SQLITE3_OPEN_CREATE|SQLITE3_OPEN_READWRITE);
    $result = $this->_pdo->query('CREATE TABLE IF NOT EXISTS snippets (id integer primary key, content text)');
  }

  public function get_pdo() {
    return $this->_pdo;
  }

  /**
   * [get_all description]
   * @return [type] [description]
   */
  public function get_all() {
    $query = $this->get_pdo()->query('SELECT id, content FROM snippets');
    $result = array();
    while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
      $result[] = $row;
    }

    return $result;
  }

  /**
   * [add description]
   * @param  array  $array   [description]
   */
  public function add($content) {
    $query = $this->get_pdo()->prepare('INSERT INTO snippets VALUES (NULL, :content)');
    $query->bindValue(':content', $content);
    return $query->execute();
  }

  public function delete($snippet_id) {
    $query = $this->get_pdo()->prepare('DELETE FROM snippets WHERE id = :id');
    $query->bindValue(':id', $snippet_id);
    return $query->execute();
  }

  public function update($id, $content) {
    $query = $this->get_pdo()->prepare('UPDATE snippets SET content = :content WHERE id = :id');
    $query->bindValue(':content', $content);
    $query->bindValue(':id', $id);
    return $query->execute();
  }

  /**
   * [get description]
   * @param  [type] $snippet_id [description]
   * @return [type]             [description]
   */
  public function get($snippet_id) {
    $query = $this->get_pdo()->prepare('SELECT id, content FROM snippets WHERE id = :id');
    $query->bindValue(':id', $snippet_id, SQLITE3_INTEGER);
    $result = $query->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
  }

}
