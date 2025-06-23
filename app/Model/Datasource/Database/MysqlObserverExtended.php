<?php
App::uses('MysqlExtended', 'Model/Datasource/Database');

/**
 * Overrides the default MySQL database implementation to support the following features:
 * - Set query hints to optimize queries
 */
class MysqlObserverExtended extends MysqlExtended
{
    public $supports = [
        'indexHints' => true,
        'ignoreIndexHints' => true,
        'reverseJoin' => true,
        'straightJoin' => true,
        'insertMulti' => true,
    ];

    /**
<<<<<<< HEAD
=======
     * Builds and generates a JOIN condition from an array. Handles final clean-up before conversion.
     *
     * @param array $join An array defining a JOIN condition in a query.
     * @return string An SQL JOIN condition to be used in a query.
     * @see DboSource::renderJoinStatement()
     * @see DboSource::buildStatement()
     */
    public function buildJoinStatement($join, $reversed_alias = null, $reversed_table = null) {
        $data = array_merge(array(
            'type' => null,
            'alias' => null,
            'table' => 'join_table',
            'conditions' => '',
        ), $join);

        if (!empty($reversed_alias)) {
            $data['alias'] = $this->name($reversed_alias);
        } else if (!empty($data['alias'])) {
            $data['alias'] = $this->alias . $this->name($data['alias']);
        }
        if (!empty($data['conditions'])) {
            $data['conditions'] = trim($this->conditions($data['conditions'], true, false));
        }
        if (!empty($reversed_table)) {
            $data['table'] = $reversed_table;
		} else if (!empty($data['table']) && (!is_string($data['table']) || strpos($data['table'], '(') !== 0)) {
			$data['table'] = $this->fullTableName($data['table']);
		}
		return $this->renderJoinStatement($data);
	}

    /**
>>>>>>> 24c3e4318 (new: [database handler rework] Unified extended and observer extended)
     * - Do not call microtime when not necessary
     * - Count query count even when logging is disabled
     *
     * @param string $sql
     * @param array $options
     * @param array $params
     * @return mixed
     */
    public function execute($sql, $options = [], $params = [])
    {
        $log = $options['log'] ?? $this->fullDebug;
        if (Configure::read('Plugin.Benchmarking_enable')) {
            $log = true;
        }
        $comment = sprintf(
            '%s%s%s',
            empty(Configure::read('CurrentUserId')) ? '' : sprintf(
                '[User: %s] ',
                intval(Configure::read('CurrentUserId'))
            ),
            empty(Configure::read('CurrentController')) ? '' : preg_replace('/[^a-zA-Z0-9_]/', '', Configure::read('CurrentController')) . ' :: ',
            empty(Configure::read('CurrentAction')) ? '' : preg_replace('/[^a-zA-Z0-9_]/', '', Configure::read('CurrentAction'))
        );
        $sql = '/* ' . $comment . ' */ ' . $sql;
        if ($log) {
            $t = microtime(true);
            $this->_result = $this->_execute($sql, $params);
            $this->took = round((microtime(true) - $t) * 1000);
            $this->numRows = $this->affected = $this->lastAffected();
            $this->logQuery($sql, $params);
        } else {
            $this->_result = $this->_execute($sql, $params);
            $this->_queriesCnt++;
        }

        return $this->_result;
    }
}
