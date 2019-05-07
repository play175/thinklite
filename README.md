thinklite
=====

极致简约，只有一个文件的 “thinkphp”

网上能找到的PHP框架对我来说似乎都太重型了，PHP本质上是一个用于快速开发小型web应用的脚本，不太适合用来开发重型web应用，另外我觉得在PHP之上再实现一套自己的模板引擎的做法是愚蠢的，所以在这个背景下，thinklite就诞生了。

**thinklite** 非常简洁，只需要引入一个文件就可以工作了，她拥有且不限于下面这些特性：

* 类似 thinkphp 框架的三级路由结构，且有比 tp 更好用的 **U** 函数用来生成路由链接

* 全能的 **I** 函数，用来捕获和验证输入参数（GET/POST/REQUEST/COOKIE）

* 基于 pdo 的数据库操作类，从根源上增加安全性，杜绝 sql 拼接，而且自带了智能的数据分页处理

* API 类型的应用和页面渲染型应用对于 contorller 层是透明的，甚至是可以共用一个 controller 类同时实现 API 数据输出和 HTML 渲染输出，该机制对于目前前后端分离的大环境非常友好

