%% Author: djay
%% Created: 16.03.2014
%% Description: GenServer для обработки очереди блокировок
%%
%%

-module(mutex_gs).
-behaviour(gen_server).

-include("../../include/structs.hrl").

-export([start_link/0, get/2, acquire/1, release/1]).
-export([init/1, handle_call/3, handle_cast/2, handle_info/2, terminate/2, code_change/3]).

%%--------------------------------------------------------------------
%% Создание экземпляра процесса
%%--------------------------------------------------------------------
start_link() ->
    gen_server:start_link({local, ?MODULE}, ?MODULE, [], []).

%%--------------------------------------------------------------------
%% Инициализация процесса
%%--------------------------------------------------------------------
init(_Args) ->
    erlang:send_after(?LOCK_CLEANUP_TIMEOUT, self(), cleanup),
    {ok, dict:new()}.

%%--------------------------------------------------------------------
%% Получить указатель на блокировку
%%
%% @param list Name
%% @param int Timeout
%%--------------------------------------------------------------------
get(Name, Timeout) ->
    gen_server:call(?MODULE, {get, Name, Timeout}).

%%--------------------------------------------------------------------
%% Установить блокировку
%% 
%% @param list Name
%%--------------------------------------------------------------------
acquire(Name) ->
    gen_server:call(?MODULE, {acquire, Name}).

%%--------------------------------------------------------------------
%% Снять блокировку
%% 
%% @param list Name
%%--------------------------------------------------------------------
release(Name) ->
    gen_server:call(?MODULE, {release, Name}).

%%--------------------------------------------------------------------
%% Получить ссылку на блокировку
%% 
%% @param Name
%% @param Timeout
%% @param State
%%--------------------------------------------------------------------
handle_call({get, Name, Timeout}, {Pid, _}, State) ->
    {reply, Name, dict:store({Pid, Name}, #lock{
        pid     = Pid, 
        name    = Name, 
        timeout = Timeout,
        state   = ?LOCK_STATE_FREE,
        created = common:microtime()
    }, State)};

%%--------------------------------------------------------------------
%% Установить блокировку
%% 
%% @param Pid
%% @param State
%%--------------------------------------------------------------------
handle_call({acquire, Name}, {Pid, _}, State) ->
    % Находим блокировку по pid
    case dict:find({Pid, Name}, State) of
        error         -> {reply, not_found, State};
        {ok, Current} ->
            % Проверяем, что еще не занята
            case Current#lock.state of
                ?LOCK_STATE_BUSY -> {reply, already_acquired, State};
                ?LOCK_STATE_FREE ->
                    % Проверяем есть ли занятые блокировки по ключу
                    Locked = dict:size(dict:filter(fun(_, Lock) -> 
                        Lock#lock.name == Current#lock.name 
                            andalso Lock#lock.state == ?LOCK_STATE_BUSY 
                            andalso Lock#lock.release > common:microtime()
                    end, State)),
                    if
                        Locked > 0 -> {reply, busy, State};
                        true       -> 
                            % Определяем время авторазблокировки
                            Release = case Current#lock.timeout of
                                false   -> common:microtime() + ?LOCK_MAX_TIMEOUT;
                                Timeout -> common:microtime() + Timeout
                            end,
                            % Блокировка успешно занята
                            {reply, acquired, dict:store(Pid, Current#lock{
                                state   = ?LOCK_STATE_BUSY, 
                                release = Release
                            }, State)}
                    end
            end
    end;

%%--------------------------------------------------------------------
%% Снять блокировку
%% Находим блокировку по pid и удаляем
%% 
%% @param Pid
%% @param State
%%--------------------------------------------------------------------
handle_call({release, Name}, {Pid, _}, State) ->
    case dict:find({Pid, Name}, State) of
        error   -> {reply, not_found, State};
        {ok, _} -> {reply, released, dict:erase(Pid, State)}
    end.

%%--------------------------------------------------------------------
%% Заглушка для обработчика входящих сообщений типа cast
%% @param State
%%--------------------------------------------------------------------
handle_cast(stop, State) ->
    {stop, normal, State}.

%%--------------------------------------------------------------------
%% Очистка протухших блокировок
%% @param State
%%--------------------------------------------------------------------
handle_info(cleanup, State) ->
    % Очищаем протухшие активные блокировки
    CleanupBusy = dict:filter(fun(_, Lock) -> 
        (Lock#lock.state == ?LOCK_STATE_BUSY 
             andalso Lock#lock.release < common:microtime()) == false end, State),
    % Ощищаем протухшие созданные блокировки
    CleanupFree = dict:filter(fun(_, Lock) ->
        MaxLiveTime = Lock#lock.created + ?LOCK_MAX_ALIVE_TIMEOUT,
        (Lock#lock.state == ?LOCK_STATE_FREE andalso MaxLiveTime < common:microtime()) == false
    end, CleanupBusy),
    
    io:fwrite("Cleanup, in pool left ~w~n", [dict:size(CleanupFree)]),
    % Повторяем через интервал
    erlang:send_after(?LOCK_CLEANUP_TIMEOUT, self(), cleanup),
    {noreply, CleanupFree}.

%%--------------------------------------------------------------------
%% Обработчик остановки процесса
%% @param State
%%--------------------------------------------------------------------
terminate(_Reason, _State) ->
    ok.

%%--------------------------------------------------------------------
%% Заглушка для горячей замены кода
%%--------------------------------------------------------------------
code_change(_OldVrs, State, _Extra) ->
    {ok, State}.