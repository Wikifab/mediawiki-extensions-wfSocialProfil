<?php

class ApiSPQueryUser extends ApiBase
{

	/**
	 * Evaluates the parameters, performs the requested query, and sets up
	 * the result. Concrete implementations of ApiBase must override this
	 * method to provide whatever functionality their module offers.
	 * Implementations must not produce any output on their own and are not
	 * expected to handle any errors.
	 *
	 * The execute() method will be invoked directly by ApiMain immediately
	 * before the result of the module is output. Aside from the
	 * constructor, implementations should assume that no other methods
	 * will be called externally on the module before the result is
	 * processed.
	 *
	 * The result data should be stored in the ApiResult object available
	 * through getResult().
	 */
	public function execute()
	{
		global $wgWikiAdminConfigExcludeUserNames;
		$ExcludeUserNames = '(';
		for ($i = 0; $i < sizeof($wgWikiAdminConfigExcludeUserNames) - 1; $i++){
			$ExcludeUserNames .= "'".$wgWikiAdminConfigExcludeUserNames[$i]."', ";
		}
		$ExcludeUserNames .= "'".$wgWikiAdminConfigExcludeUserNames[sizeof($wgWikiAdminConfigExcludeUserNames) - 1]."')";

		$query = $this->getParameter('query');

        $dbr = wfGetDB( DB_MASTER );
        $query = $dbr->strencode(strtolower($query));

		if($query == 'emptycontent'){
            $result = $dbr->select(
                ['user'],
                ['user_name', 'user_id'],
                [
                	'user_name NOT IN '.$ExcludeUserNames
                ],
                __METHOD__,
                [
					'ORDER BY' => 'user_name ASC',
                	'LIMIT' => 10
				]
            );
        } else {
        	//convert to utf8 for case insensitive search
            $result = $dbr->select(
                ['user'],
                ['user_name', 'user_id'],
                [
                	'user_name NOT IN '.$ExcludeUserNames,
                    'CONVERT(user_name USING utf8) LIKE "%'.$query.'%" OR CONVERT(user_real_name USING utf8) LIKE "%'.$query.'%"'
                ],
                __METHOD__,
                ['LIMIT' => 10]
            );
        }

		$data = [];
		foreach($result as $row){
			$avatar = new wAvatar($row->user_id, 's');
			$user['name'] = $row->user_name;
			$user['avatar'] = $avatar->getAvatarURL();
			$data[] = $user;
		}

		$this->getResult()->addValue(null, 'results', $data);
	}


	protected function getAllowedParams( /* $flags = 0 */)
	{
		return parent::getAllowedParams() + [
			'query' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			]
		];
	}
}