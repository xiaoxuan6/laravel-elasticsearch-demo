# 启动

```bash
docker-compose up -d
```

# 常见错误

## 1、apt-get update 时报错：`Err http://deb.debian.org jessie Release.gpg Could not resolve 'deb.debian.org'`

## 2、Docker中使用 git clone 报错 Could not resolve host: github.com；

### 解决方法一：(不推荐，每次重启机器 resolv.conf 会自动恢复)

1、在docker所在环境中执行：修改 `resolv.conf`

```bash
vim /etc/resolv.conf

nameserver 8.8.8.8
```

2、重启docker

```bash
service docker restart
```

### 解决方法二、(推荐)

1、找出宿主机的 `dns`：

```bash
cat /etc/resolv.conf
```

2、编辑 `/etc/docker/daemon.json` 文件

```bash
{                                                                          
    "dns": ["x.x.x.x"]                                                                           
} 
```

重启docker服务： `systemctl restart docker`
