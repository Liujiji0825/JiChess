// 初始化
var board = null;
var game = new Chess();

// 載入 Stockfish 19 核心啟動器
var engine = new Worker('js/stockfish-19-single.js');

// 啟動並設定 UCI 協定
engine.postMessage('uci');

// 檢查對局是否結束、儲存棋譜
function checkGameOver() {
    if (game.game_over()) {
        var result = '1/2-1/2'; // 預設平局

        // 判斷 Checkmate
        if (game.in_checkmate()) {
            result = game.turn() === 'b' ? '1-0' : '0-1';
        }

        var pgn = game.pgn();

        // 傳送到後端
        fetch('save_game.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                pgn: pgn,
                result: result
            })
        })
        .then(response => response.json())
        .then(data => {
            // 收到 PHP 回傳結果後，彈出視窗通知玩家
            var resultText = result === '1-0' ? '白方獲勝！' : (result === '0-1' ? '黑方獲勝！' : '平局！');
            if (data.status === 'success') {
                alert("遊戲結束！" + resultText + "\n" + data.message);
            } else {
                alert("遊戲結束，但儲存失敗：" + data.message);
            }
        })
        .catch(error => console.error('Error saving game:', error));
    }
}

// 接收引擎計算結果
engine.onmessage = function(event) {
    var line = event.data;

    if (line && line.indexOf('bestmove') > -1) {

        var match = line.match(/^bestmove ([a-h][1-8])([a-h][1-8])([qrob])?/);
        if (match) {
            // 讓黑棋走這一步
            game.move({
                from: match[1],
                to: match[2],
                promotion: match[3] ? match[3] : 'q'
            });
            board.position(game.fen());

            checkGameOver();
        }
    }
};

function makeEngineMove() {
    if (game.game_over()) return;

    engine.postMessage('position fen ' + game.fen());
    engine.postMessage('go depth 15');
}

// 玩家試圖拿起棋子時的規則
function onDragStart (source, piece, position, orientation) {
    // 將殺、逼和，或是遊戲結束，不能移動
    if (game.game_over()) return false;

    // 目前測試階段，限制只能移動白棋
    if (piece.search(/^b/) !== -1) return false;
}

// 玩家放下棋子時的規則
function onDrop (source, target) {
    // 這步棋是否合法
    var move = game.move({
        from: source,
        to: target,
        promotion: 'q' // 預設升變為后
    });

    // 如果不合法，棋子會回到原本位置
    if (move === null) return 'snapback';

    if (game.game_over()) {
        checkGameOver();
        return;
    }

    // 關鍵在這裡，如果白方走了一步合法的棋，讓電腦延遲
    window.setTimeout(makeEngineMove, 250);
}

// 確保落子後畫面與邏輯同步
function onSnapEnd () {
    board.position(game.fen());
}

// 將上面的規則打包成設定檔
var config = {
    draggable: true,
    position: 'start',
    onDragStart: onDragStart,
    onDrop: onDrop,
    onSnapEnd: onSnapEnd,

    pieceTheme: 'img/chesspieces/wikipedia/{piece}.png'
};

// 把棋盤畫在 myBoard
board = Chessboard('myBoard', config);