#!/bin/bash

SCRIPT_DIR="$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

# Settings
BIN_PATH="/usr/bin/"
CLUSTER_HOST=127.0.0.1
PORT=7000
TIMEOUT=2000
NODES=14
REPLICAS=0
PROTECTED_MODE=yes
ADDITIONAL_OPTIONS=""

# You may want to put the above config parameters into config.sh in order to
# override the defaults without modifying this script.

if [ -a config.sh ]
then
    source "config.sh"
fi

# Computed vars
ENDPORT=$((PORT+NODES))

if [ "$1" == "start" ]
then
    # read port from argument or use PORT default
    if [ "$2" != "" ]; then
        PORT=$2
    fi
    # read endport from argument or use ENDPORT default
    if [ "$3" != "" ]; then
        ENDPORT=$3
    fi
    while [ $((PORT < ENDPORT)) != "0" ]; do
        PORT=$((PORT+1))
        echo "Starting $PORT"
        $BIN_PATH/redis-server --port $PORT --protected-mode $PROTECTED_MODE --cluster-enabled yes --cluster-config-file nodes-${PORT}.conf --cluster-node-timeout $TIMEOUT --appendonly yes --appendfilename appendonly-${PORT}.aof --appenddirname appendonlydir-${PORT} --dbfilename dump-${PORT}.rdb --logfile ${PORT}.log --daemonize yes ${ADDITIONAL_OPTIONS}
    done
    exit 0
fi

if [ "$1" == "create" ]
then
    # read port from argument or use PORT default
    if [ "$2" != "" ]; then
        PORT=$2
    fi
    # read endport from argument or use ENDPORT default
    if [ "$3" != "" ]; then
        ENDPORT=$3
    fi
    HOSTS=""
    while [ $((PORT < ENDPORT)) != "0" ]; do
        PORT=$((PORT+1))
        HOSTS="$HOSTS $CLUSTER_HOST:$PORT"
    done
    OPT_ARG=""
    if [ "$4" == "-f" ]; then
        OPT_ARG="--cluster-yes"
    fi
    echo "Creating cluster with $HOSTS"
    $BIN_PATH/redis-cli --cluster create $HOSTS --cluster-replicas $REPLICAS $OPT_ARG
    exit 0
fi

if [ "$1" == "stop" ]
then
    # read port from argument or use PORT default
    if [ "$2" != "" ]; then
        PORT=$2
    fi
    # read endport from argument or use ENDPORT default
    if [ "$3" != "" ]; then
        ENDPORT=$3
    fi
    while [ $((PORT < ENDPORT)) != "0" ]; do
        PORT=$((PORT+1))
        echo "Stopping $PORT"
        $BIN_PATH/redis-cli -p $PORT shutdown nosave
    done
    exit 0
fi

if [ "$1" == "watch" ]
then
    PORT=$((PORT+1))
    while [ 1 ]; do
        clear
        date
        $BIN_PATH/redis-cli -p $PORT cluster nodes | head -30
        sleep 1
    done
    exit 0
fi

if [ "$1" == "tail" ]
then
    INSTANCE=$2
    PORT=$((PORT+INSTANCE))
    tail -f ${PORT}.log
    exit 0
fi

if [ "$1" == "tailall" ]
then
    tail -f *.log
    exit 0
fi

if [ "$1" == "call" ]
then
    while [ $((PORT < ENDPORT)) != "0" ]; do
        PORT=$((PORT+1))
        $BIN_PATH/redis-cli -p $PORT $2 $3 $4 $5 $6 $7 $8 $9
    done
    exit 0
fi

if [ "$1" == "clean" ]
then
    echo "Cleaning *.log"
    rm -rf *.log
    echo "Cleaning appendonlydir-*"
    rm -rf appendonlydir-*
    echo "Cleaning dump-*.rdb"
    rm -rf dump-*.rdb
    echo "Cleaning nodes-*.conf"
    rm -rf nodes-*.conf
    exit 0
fi

if [ "$1" == "clean-logs" ]
then
    echo "Cleaning *.log"
    rm -rf *.log
    exit 0
fi

echo "Usage: $0 [start|create|stop|watch|tail|tailall|clean|clean-logs|call]"
echo "start       -- Launch Redis Cluster instances."
echo "create [-f] -- Create a cluster using redis-cli --cluster create."
echo "stop        -- Stop Redis Cluster instances."
echo "watch       -- Show CLUSTER NODES output (first 30 lines) of first node."
echo "tail <id>   -- Run tail -f of instance at base port + ID."
echo "tailall     -- Run tail -f for all the log files at once."
echo "clean       -- Remove all instances data, logs, configs."
echo "clean-logs  -- Remove just instances logs."
echo "call <cmd>  -- Call a command (up to 7 arguments) on all nodes."
