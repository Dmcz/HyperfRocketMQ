# 🚧🚧🚧 本库仍在开发中仍在开发中，功能尚未完全实现！！！
# 🚧🚧🚧 This library is under development. Not all features are implemented!!!

# 项目简介
这是一个 PHP RocketMQ 客户端。基于 Hyperf gRPC（Swoole HTTP2）实现，未采用 PHP gRPC 扩展的 client/stub 方案，而是重写了 protobuf 生成的 stub，因此暂不计划并入 rocketmq-clients。

# 环境要求
必须启用 `swoole` 与 `grpc` 扩展。

# 其他
## Protobuf 生成命令
> 仅用于本库内部 protobuf 文件更新/生成时的命令参考。使用本库时无需执行此命令。
protoc \
  -I /code/rocketmq-clients/protos \
  -I /usr/include \
  --php_out=generated/protocol \
  --grpc_out=generated/stub/grpc \
  --plugin=protoc-gen-grpc=$(which grpc_php_plugin) \
  /code/rocketmq-clients/protos/apache/rocketmq/v2/*.proto
