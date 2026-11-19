# Socket.IO 相容 Parser

`Windwalker\WebSocket\Parser\SocketIoParser` 實作 `WebSocketParserInterface`，可解析／產生 Engine.IO v4 + Socket.IO v5 的**文字訊框**封包結構。

## 支援範圍

封包格式：`<engine><type>[<attachments>-][<namespace>,][<id>]<json>`

| 層級 | 支援 |
|------|------|
| Engine.IO | `0 open`、`1 close`、`2 ping`、`3 pong`、`4 message`、`5 upgrade`、`6 noop`（`open` 的 JSON 會被解碼） |
| Socket.IO | `CONNECT`、`DISCONNECT`、`EVENT`、`ACK`、`CONNECT_ERROR`、`BINARY_EVENT`、`BINARY_ACK`（僅 header） |
| 欄位 | namespace（`/chat,`）、ack id、事件名稱、參數列表 |

### parse()

回傳 `engine, type, namespace, id, attachments, name, args, data`。

```php
$parser->parse('42/chat,12["msg","x"]');
// engine=4 type=2 namespace=/chat id=12 name=msg args=['x']
```

### format()

```php
$parser->format('hi', 1);                                         // 42["hi",1]
$parser->format(name: 'hi', args: [1], namespace: '/chat', id: 5); // 42/chat,5["hi",1]
$parser->format(type: SocketIoParser::ACK, id: 1, args: ['ok']);   // 431["ok"]
$parser->format(engine: SocketIoParser::ENGINE_PONG);              // 3
```

### handleRequest()

- EVENT：`requestTarget` = 事件名稱，`parsedData` = 參數陣列，可直接用 `WsRouter` 路由。
- 其他封包：`requestTarget` = `socket.io:<type>` 或 `engine.io:<type>`，`parsedData` 為完整封包。
- 完整封包永遠放在 request attribute `socketio`（含 ack id、namespace）。

## 限制（未實作）

Parser 只做無狀態的資料結構轉換，以下需另行實作：

1. **握手與傳輸**：Socket.IO client 預設先走 HTTP long-polling（`/socket.io/?EIO=4&transport=polling`）取得 `sid` 再升級。需 client 設定 `transports: ['websocket']`，並由 server 在連線時自行送 `0{"sid":...,"pingInterval":...,"pingTimeout":...}` 與 `40{"sid":...}`。Polling 的 `\x1e` 多封包分隔也未處理。
2. **心跳**：server 需以 timer 定時送 `2`，並處理 client 回覆的 `3`（v4 為 server 發 ping）。
3. **Ack 回覆**：parser 只解析 id；handler 需自己從 `socketio` attribute 取出 id/namespace，用 `format(type: ACK, ...)` 回應。
4. **Namespace 驗證、Room、Broadcast**：屬連線狀態管理，需自建（如 Swoole table/Redis）。
5. **二進位事件**：只解析 `5-` / `6-` header 的 attachments 數量；payload 內 `{"_placeholder":true,"num":0}` 不會被還原，後續 binary frame 也不會被組合（`parse()` 僅接受字串）。
6. **Middleware / 錯誤封包**：`CONNECT_ERROR` 僅解析資料，不自動產生。
7. **相容版本**：僅 EIO=4 / Socket.IO v5（client v3+）；v2 以前格式不支援。
