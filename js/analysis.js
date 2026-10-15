var board = null;
var game = new Chess();

// ==============================
// Move Tree 資料結構
// ==============================
var nodeIdCounter = 0;
var nodeMap = {};
var rootNode = null;
var currentNode = null;

function createNode(san, fen, parent, ply) {
    var node = {
        id: nodeIdCounter++,
        san: san,           // 文字
        fen: fen,           // FEN
        parent: parent,     // 上一步的節點
        children: [],       // 下一步的陣列
        ply: ply            // 半步數 (1=白1, 2=黑1, 3=白2...)
    };
    nodeMap[node.id] = node;
    return node;
}

function initTree(startFen) {
    nodeIdCounter = 0;
    nodeMap = {};
    var fen = startFen || 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
    game.load(fen);

    // 計算初始局面的半步數
    var parts = fen.split(' ');
    var moveNum = parseInt(parts[5]) || 1;
    var turn = parts[1] || 'w';
    var startPly = (moveNum - 1) * 2 + (turn === 'b' ? 1 : 0);

    rootNode = createNode(null, fen, null, startPly);
    currentNode = rootNode;
}

// ==============================
// Stockfish 19 分析整合
// ==============================
var engine = new Worker('js/stockfish-19-lite-single.js');
engine.onerror = function(err) {
    alert("AI 引擎發生錯誤：" + err.message);
    console.error("Worker Error:", err);
};

engine.postMessage('uci');

var analysisTimer = null; // 用來防止連按導致引擎卡死的計時器

engine.onmessage = function(event) {
    var line = event.data;
    if (!line) return;

    // 解析 Stockfish 的評估分數與最佳變化
    if (line.indexOf('info depth') !== -1 && line.indexOf(' pv ') !== -1) {
        var scoreText = "0.00";
        var turnMultiplier = (game.turn() === 'w') ? 1 : -1; // 統一轉成白方視角

        var cpMatch = line.match(/score cp (-?\d+)/);
        var mateMatch = line.match(/score mate (-?\d+)/);

        if (mateMatch) {
            var mateIn = parseInt(mateMatch[1]) * turnMultiplier;
            scoreText = (mateIn > 0 ? "+M" : "-M") + Math.abs(mateIn);
        } else if (cpMatch) {
            var cp = (parseInt(cpMatch[1]) * turnMultiplier) / 100;
            scoreText = (cp > 0 ? "+" : "") + cp.toFixed(2);
        }
        document.getElementById('eval-score').innerText = scoreText;

        var pvMatch = line.match(/ pv (.+)/);
        if (pvMatch) {
            var uciMoves = pvMatch[1].trim().split(' ');
            var tempGame = new Chess(currentNode.fen); // 建立一個虛擬棋盤來推演
            var sanMoves = [];
            
            // 為了不讓畫面太擠，我們只抓前 5 步最佳建議
            for (var i = 0; i < Math.min(uciMoves.length, 5); i++) {
                var uci = uciMoves[i];
                var source = uci.substring(0, 2);
                var target = uci.substring(2, 4);
                var promo = uci.length > 4 ? uci.substring(4, 5) : 'q';
                
                var moveObj = tempGame.move({ from: source, to: target, promotion: promo });
                if (moveObj) {
                    sanMoves.push(moveObj.san);
                } else {
                    break; 
                }
            }
            document.getElementById('engine-line').innerText = "最佳建議: " + sanMoves.join(' ') + " ...";
        }
    }
};

function analyzeCurrentPosition() {
    if (game.game_over()) {
        document.getElementById('eval-score').innerText = "END";
        document.getElementById('engine-line').innerText = "對局結束";
        engine.postMessage('stop');
        return;
    }
    document.getElementById('engine-line').innerText = "計算中...";
    engine.postMessage('stop');

    clearTimeout(analysisTimer);
    analysisTimer = setTimeout(function() {
        engine.postMessage('position fen ' + currentNode.fen);
        engine.postMessage('go depth 16');
    }, 250);
}

// ==============================
// 棋盤拖曳與落子邏輯
// ==============================
function onDragStart (source, piece, position, orientation) {
    if (game.game_over()) return false;
    if ((game.turn() === 'w' && piece.search(/^b/) !== -1) ||
        (game.turn() === 'b' && piece.search(/^w/) !== -1)) {
        return false;
    }
}

function onDrop (source, target) {
    var move = game.move({
        from: source,
        to: target,
        promotion: 'q'
    });

    if (move === null) return 'snapback';

    // 檢查這步棋有沒有在 children 中
    var existingChild = null;
    for (var i = 0; i < currentNode.children.length; i++) {
        if (currentNode.children[i].san === move.san) {
            existingChild = currentNode.children[i];
            break;
        }
    }

    if (existingChild) {
        currentNode = existingChild;
    } else {
        var newNode = createNode(move.san, game.fen(), currentNode, currentNode.ply + 1);
        currentNode.children.push(newNode);
        currentNode = newNode;
    }

    renderMoveList();
    analyzeCurrentPosition();
}

function onSnapEnd () {
    board.position(game.fen());
}

// 初始化棋盤
var config = {
    draggable: true,
    position: 'start',
    onDragStart: onDragStart,
    onDrop: onDrop,
    onSnapEnd: onSnapEnd,
    pieceTheme: 'img/chesspieces/wikipedia/{piece}.png',
};
board = Chessboard('myBoard', config);
initTree();

// ==============================
// 棋譜渲染引擎
// ==============================
function renderMoveList() {
    var container = document.getElementById('move-list');
    var html = '';
    var curr = rootNode;

    var pendingBlackVars = [];

    while (curr.children.length > 0) {
        var mainChild = curr.children[0];
        var isWhite = (mainChild.ply % 2 === 1);
        var moveNum = Math.ceil(mainChild.ply / 2);

        if (isWhite) {
            var whiteNode = mainChild;
            var whiteVars = curr.children.slice(1); // 分叉自白方這一步的支線
            var blackNode = whiteNode.children.length > 0 ? whiteNode.children[0] : null;
            var blackVars = whiteNode.children.length > 1 ? whiteNode.children.slice(1) : [];

            // 情況 A
            if (whiteVars.length > 0) {
                html += buildRowHtml(moveNum, whiteNode, null);
                html += buildVariationsBoxHtml(whiteVars);
                if (blackNode) {
                    html += buildRowHtml(moveNum, null, blackNode);
                    if (blackVars.length > 0) {
                        html += buildVariationsBoxHtml(blackVars);
                    }
                    curr = blackNode;
                } else {
                    break;
                }
            } else {
                // 情況 B
                html += buildRowHtml(moveNum, whiteNode, blackNode);
                if (blackNode) {
                    if (blackVars.length > 0) {
                        html += buildVariationsBoxHtml(blackVars);
                    }
                    curr = blackNode;
                } else {
                    break;
                }
            }
        } else {
            // 只有匯入的 FEN 是黑方先動時才會到這裡
            var blackNode = mainChild;
            var blackVars = curr.children.slice(1);
            html += buildRowHtml(moveNum, null, blackNode);
            if (blackVars.length > 0) {
                html += buildVariationsBoxHtml(blackVars);
            }
            curr = blackNode;
        }
    }

    container.innerHTML = html;

    // 自動捲動到當前步數
    var activeEl = container.querySelector('.active');
    if (activeEl) {
        activeEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
}

// 產生主線 HTML
function buildRowHtml(moveNum, whiteNode, blackNode) {
    var html = '<div class="move-row">';
    html += '<div class="move-num">' + moveNum + '.</div>';

    if (whiteNode) {
        var wActive = (currentNode.id === whiteNode.id) ? ' active' : '';
        html += '<div class="move-cell' + wActive + '" onclick="jumpToNode(' + whiteNode.id + ')">' + whiteNode.san + '</div>';
    } else {
        html += '<div class="move-cell empty">...</div>';
    }

    if (blackNode) {
        var bActive = (currentNode.id === blackNode.id) ? ' active' : '';
        html += '<div class="move-cell' + bActive + '" onclick="jumpToNode(' + blackNode.id + ')">' + blackNode.san + '</div>';
    } else {
        html += '<div class="move-cell empty"></div>';
    }

    html += '</div>';
    return html
}

// 產生支線 HTML
function buildVariationsBoxHtml(varNodes) {
    var html = '<div class="variation-box">';
    for (var i = 0; i < varNodes.length; i++) {
        html += '<div class="variation-line">↳ ' + renderVariationLine(varNodes[i], true) + '</div>';
    }
    html += '</div>';
    return html;
}

// 遞迴渲染支線與括號內的子支線
function renderVariationLine(node, forceMoveNum) {
    var html = '';
    var curr = node;
    var needNum = forceMoveNum;
    var delayedSubVars = [];

    while (curr) {
        var isWhite = (curr.ply % 2 === 1);
        var moveNum = Math.ceil(curr.ply / 2);
        var prefix = '';

        if (isWhite) {
            prefix = moveNum + '. ';
        } else if (needNum) {
            prefix = moveNum + '... ';
        }

        var activeClass = (currentNode.id === curr.id) ? ' active' : '';
        html += '<span class="var-move' + activeClass + '" onclick="jumpToNode(' + curr.id + ')">' + prefix + curr.san + '</span> ';

        if (delayedSubVars.length > 0) {
            for (var i = 0; i < delayedSubVars.length; i++) {
                html += '<span class="sub-variation">( ' + renderVariationLine(delayedSubVars[i], true) + ')</span> ';
            }
            delayedSubVars = [];
            needNum = true; // 括號結束後，如果繼續是黑棋，強制補上 3...
        } else {
            needNum = false;
        }

        // 收集目前這步棋的「子支線」，留到下一次迴圈印出
        if (curr.children.length > 1) {
            delayedSubVars = curr.children.slice(1);
        }

        curr = curr.children.length > 0 ? curr.children[0] : null;
    }

    if (delayedSubVars.length > 0) {
        for (var i = 0; i < delayedSubVars.length; i++) {
            html += '<span class="sub-variation">( ' + renderVariationLine(delayedSubVars[i], true) + ')</span> ';
        }
    }

    return html;
}

// ==============================
// 節點跳轉與導航按鈕控制
// ==============================
function jumpToNode(nodeId) {
    var target = nodeMap[nodeId];
    if (!target) return;

    currentNode = target;
    game.load(currentNode.fen);
    board.position(currentNode.fen);

    renderMoveList();
    analyzeCurrentPosition();
}

// 下一步：永遠第一個子節點優先
function nextMove() {
    if (currentNode.children.length > 0) {
        jumpToNode(currentNode.children[0].id);
    }
}

// 上一步
function prevMove() {
    if (currentNode.parent) {
        jumpToNode(currentNode.parent.id);
    }
}

// 回到一開始
function goToStart() {
    jumpToNode(rootNode.id);
}

// 到最後一步
function goToEnd() {
    var curr = currentNode;
    while (curr.children.length > 0) {
        curr = curr.children[0];
    }
    jumpToNode(curr.id);
}

// ==============================
// 選單與載入 PGN / FEN 功能
// ==============================
function showBoardArea() {
    if (document.getElementById('setup-menu')) {
        document.getElementById('setup-menu').style.display = 'none';
    }
    document.getElementById('board-area').style.display = 'flex';
    board.resize();
    renderMoveList();
    analyzeCurrentPosition();
}

function backToMenu() {
    engine.postMessage('stop');
    if (document.getElementById('setup-menu')) {
        document.getElementById('setup-menu').style.display = 'block';
        document.getElementById('board-area').style.display = 'none';
    } else {
        window.location.href = 'analysis.php';
    }
}

// 載入 PGN
function loadPgnForReplay(pgnStr) {
    var tempGame = new Chess();
    if (!tempGame.load_pgn(pgnStr)) {
        alert("PGN 格式解析失敗！");
        return;
    }

    var history = tempGame.history();

    var headers = tempGame.header();
    var startFen = (headers.SetUp === '1' && headers.FEN) ? headers.FEN : null;

    initTree(startFen);

    for (var i = 0; i < history.length; i++) {
        game.move(history[i]);
        var newNode = createNode(history[i], game.fen(), currentNode, currentNode.ply + 1);
        currentNode.children.push(newNode);
        currentNode = newNode;
    }

    currentNode = rootNode;
    game.load(rootNode.fen);
    board.position(rootNode.fen);

    showBoardArea();
}

// 新分析
function startNewAnalysis() {
    initTree();
    board.position('start');
    showBoardArea();
}

// 棋盤編輯器
function openBoardEditor() {
    alert("棋盤編輯器尚在開發中！");
}

// 匯入
function showImportBox() {
    document.getElementById('import-box').style.display = 'block';
}

function importData() {
    var text = document.getElementById('import-text').value.trim();
    if (text === "") return;

    if (text.indexOf('/') !== -1 && text.indexOf('\n') === -1) {
        var tempGame = new Chess();
        if (tempGame.load(text)) {
            initTree(text);
            board.position(text);
            showBoardArea();
        } else {
            alert("無效的 FEN 格式！");
        }
    } else {
        loadPgnForReplay(text);
    }
}

// 支援鍵盤上下左右
document.addEventListener('keydown', function(e) {
    if (document.getElementById('board-area').style.display === 'none') return;
    if (e.key === 'ArrowRight') nextMove();
    if (e.key === 'ArrowLeft') prevMove();
});

// 有 PGN，直接載入
if (loadedPgn && loadedPgn.trim() !== "") {
    loadPgnForReplay(loadedPgn);
}