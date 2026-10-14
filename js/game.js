// 初始化
var board = null;
var game = new Chess();

// 載入 Stockfish 19 核心啟動器
var engine = new Worker('js/stockfish-19-lite-single.js');

// 啟動並設定 UCI 協定
engine.postMessage('uci');

// 支援正在下棋時可以回顧前面下的棋
var gameHistory = [{
    san: 'Start',
    fen: 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1'
}];
var currentViewIndex = 0;

// 檢查對局是否結束、儲存棋譜
// 檢查對局是否結束、儲存棋譜
function checkGameOver() {
    if (game.game_over()) {
        var result = '1/2-1/2'; 
        if (game.in_checkmate()) {
            result = game.turn() === 'b' ? '1-0' : '0-1';
        }

        var pgn = game.pgn();

        fetch('save_game.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pgn: pgn, result: result })
        })
        .then(response => response.json())
        .then(data => {
            var resultText = result === '1-0' ? '白方獲勝！' : (result === '0-1' ? '黑方獲勝！' : '平局！');
            
            // ========== UI 動線更新 ==========
            // 1. 把「放棄並回大廳」改成「回到大廳」
            document.getElementById('btn-resign').innerText = "回到大廳";
            // 2. 顯示「查看棋譜」的按鈕
            document.getElementById('btn-history').style.display = "inline-block";
            // =================================

            setTimeout(() => {
                if (data.status === 'success') {
                    alert("遊戲結束！" + resultText + "\n" + data.message);
                } else {
                    alert("遊戲結束，但儲存失敗：" + data.message);
                }
            }, 500);
        })
        .catch(error => console.error('Error saving game:', error));
    }
}

// 渲染棋譜列表
function renderMoveList() {
    var container = document.getElementById('move-list');
    var html = '';
    
    // gameHistory[0] 是初始局面，真正的棋步從 1 開始
    for (var i = 1; i < gameHistory.length; i += 2) {
        var moveNum = Math.ceil(i / 2);
        var whiteMove = gameHistory[i];
        var blackMove = gameHistory[i + 1];

        html += '<div class="move-row">';
        html += '<div class="move-num">' + moveNum + '.</div>';
        
        // 白方
        var wActive = (currentViewIndex === i) ? ' active' : '';
        html += '<div class="move-cell' + wActive + '" onclick="viewPastMove(' + i + ')">' + whiteMove.san + '</div>';
        
        // 黑方
        if (blackMove) {
            var bActive = (currentViewIndex === i + 1) ? ' active' : '';
            html += '<div class="move-cell' + bActive + '" onclick="viewPastMove(' + (i + 1) + ')">' + blackMove.san + '</div>';
        } else {
            html += '<div class="move-cell empty"></div>';
        }
        
        html += '</div>';
    }

    container.innerHTML = html;

    // 自動捲動到底部，或高亮所在的步數
    var activeEl = container.querySelector('.active');
    if (activeEl) {
        activeEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
}

// 點擊棋譜查看舊局勢
function viewPastMove(index) {
    currentViewIndex = index;
    board.position(gameHistory[index].fen);
    renderMoveList();
    
    // 如果看的不是最新一步，顯示「返回最新局勢」按鈕
    if (currentViewIndex !== gameHistory.length - 1) {
        document.getElementById('btn-latest').style.display = 'inline-block';
    } else {
        document.getElementById('btn-latest').style.display = 'none';
    }
}

// 回到最新局勢 (恢復下棋權限)
function goToLatestMove() {
    currentViewIndex = gameHistory.length - 1;
    board.position(gameHistory[currentViewIndex].fen);
    renderMoveList();
    document.getElementById('btn-latest').style.display = 'none';
}

// 接收引擎計算結果
engine.onmessage = function(event) {
    var line = event.data;

    if (line && line.indexOf('bestmove') > -1) {

        var match = line.match(/^bestmove ([a-h][1-8])([a-h][1-8])([qrob])?/);
        if (match) {
            // 讓黑棋走這一步
            var move = game.move({
                from: match[1],
                to: match[2],
                promotion: match[3] ? match[3] : 'q'
            });
            // 記錄黑方(電腦)這一步
            gameHistory.push({ san: move.san, fen: game.fen() });
            
            // 如果玩家沒有在看舊棋譜，就自動切換到最新局面
            if (currentViewIndex === gameHistory.length - 2) {
                currentViewIndex++;
                board.position(game.fen());
            }
            renderMoveList();
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

    if (currentViewIndex !== gameHistory.length - 1) {
        alert("請先點擊「返回最新局勢」才能繼續下棋！");
        return false;
    }

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

    gameHistory.push({ san: move.san, fen: game.fen() });
    currentViewIndex++;
    renderMoveList();

    if (game.game_over()) {
        checkGameOver();
        return;
    }

    // 關鍵在這裡，如果白方走了一步合法的棋，讓電腦延遲
    window.setTimeout(makeEngineMove, 250);
}

// 確保落子後畫面與邏輯同步
function onSnapEnd () {
    if (currentViewIndex === gameHistory.length - 1) {
        board.position(game.fen());
    }
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