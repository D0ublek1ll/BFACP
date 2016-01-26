<?php

namespace BFACP\Http\Controllers\Api;

use BFACP\Battlefield\Chat;
use BFACP\Battlefield\Server\Server;
use BFACP\Facades\Main as MainHelper;
use Carbon\Carbon;

/**
 * Class ChatlogController.
 */
class ChatlogController extends Controller
{
    protected $chat;

    protected $server;

    /**
     * @param Chat   $chat
     * @param Server $server
     */
    public function __construct(Chat $chat, Server $server)
    {
        $this->chat = $chat;
        $this->server = $server;
    }

    /**
     * @return mixed
     */
    public function getIndex()
    {
        $limit = 100;

        $chat = $this->chat->leftJoin('tbl_server', 'tbl_chatlog.ServerID', '=',
            'tbl_server.ServerID')->select('tbl_chatlog.*', 'tbl_server.ServerName')->orderBy('logDate', 'desc');

        if ($this->request->has('limit') && in_array($this->request->get('limit'), range(10, 100, 10))) {
            $limit = $this->request->get('limit');
        }

        if ($this->request->has('nospam') && $this->request->get('nospam') == 1) {
            $chat = $chat->excludeSpam();
        }

        if ($this->request->has('between')) {
            $between = explode(',', $this->request->get('between'));

            $startDate = Carbon::createFromFormat('Y-m-d H:i:s', $between[0]);

            if (count($between) == 1) {
                $endDate = Carbon::now();
            } else {
                $endDate = Carbon::createFromFormat('Y-m-d H:i:s', $between[1]);
            }

            if ($startDate->gte($endDate)) {
                return MainHelper::response(null,
                    sprintf('%s is greater than %s. Please adjust your dates.', $startDate->toDateTimeString(),
                        $endDate->toDateTimeString()), 'error', null, false, true);
            }

            $chat = $chat->whereBetween('logDate', [
                $startDate->toDateTimeString(),
                $endDate->toDateTimeString(),
            ])->paginate($limit);
        } else {
            $chat = $chat->simplePaginate($limit);
        }

        return MainHelper::response($chat, null, null, null, false, true);
    }
}
