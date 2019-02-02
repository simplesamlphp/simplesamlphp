<?php

namespace Predis;

/**
 * Client class used for connecting and executing commands on Redis.
 *
 * This is the main high-level abstraction of Predis upon which various other
 * abstractions are built. Internally it aggregates various other classes each
 * one with its own responsibility and scope.
 *
 * {@inheritdoc}
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Client
{
    /**
     * @param mixed $parameters Connection parameters for one or more servers.
     * @param mixed $options    Options to configure some behaviours of the client.
     */
    public function __construct($parameters = null, $options = null)
    {
    }

    /**
     * Creates a new instance of Predis\Configuration\Options from different
     * types of arguments or simply returns the passed argument if it is an
     * instance of Predis\Configuration\OptionsInterface.
     *
     * @param mixed $options Client options.
     *
     * @throws \InvalidArgumentException
     *
     * @return OptionsInterface
     */
    protected function createOptions($options)
    {
    }

    /**
     * Creates single or aggregate connections from different types of arguments
     * (string, array) or returns the passed argument if it is an instance of a
     * class implementing Predis\Connection\ConnectionInterface.
     *
     * Accepted types for connection parameters are:
     *
     *  - Instance of Predis\Connection\ConnectionInterface.
     *  - Instance of Predis\Connection\ParametersInterface.
     *  - Array
     *  - String
     *  - Callable
     *
     * @param mixed $parameters Connection parameters or connection instance.
     *
     * @throws \InvalidArgumentException
     *
     * @return ConnectionInterface
     */
    protected function createConnection($parameters)
    {
    }

    /**
     * Wraps a callable to make sure that its returned value represents a valid
     * connection type.
     *
     * @param mixed $callable
     *
     * @return \Closure
     */
    protected function getConnectionInitializerWrapper($callable)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getProfile()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
    }

    /**
     * Creates a new client instance for the specified connection ID or alias,
     * only when working with an aggregate connection (cluster, replication).
     * The new client instances uses the same options of the original one.
     *
     * @param string $connectionID Identifier of a connection.
     *
     * @throws \InvalidArgumentException
     *
     * @return Client
     */
    public function getClientFor($connectionID)
    {
    }

    /**
     * Opens the underlying connection and connects to the server.
     */
    public function connect()
    {
    }

    /**
     * Closes the underlying connection and disconnects from the server.
     */
    public function disconnect()
    {
    }

    /**
     * Closes the underlying connection and disconnects from the server.
     *
     * This is the same as `Client::disconnect()` as it does not actually send
     * the `QUIT` command to Redis, but simply closes the connection.
     */
    public function quit()
    {
    }

    /**
     * Returns the current state of the underlying connection.
     *
     * @return bool
     */
    public function isConnected()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
    }

    /**
     * Retrieves the specified connection from the aggregate connection when the
     * client is in cluster or replication mode.
     *
     * @param string $connectionID Index or alias of the single connection.
     *
     * @throws NotSupportedException
     *
     * @return Connection\NodeConnectionInterface
     */
    public function getConnectionById($connectionID)
    {
    }

    /**
     * Executes a command without filtering its arguments, parsing the response,
     * applying any prefix to keys or throwing exceptions on Redis errors even
     * regardless of client options.
     *
     * It is possible to identify Redis error responses from normal responses
     * using the second optional argument which is populated by reference.
     *
     * @param array $arguments Command arguments as defined by the command signature.
     * @param bool  $error     Set to TRUE when Redis returned an error response.
     *
     * @return mixed
     */
    public function executeRaw(array $arguments, &$error = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function __call($commandID, $arguments)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createCommand($commandID, $arguments = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
    }

    /**
     * Handles -ERR responses returned by Redis.
     *
     * @param CommandInterface       $command  Redis command that generated the error.
     * @param ErrorResponseInterface $response Instance of the error response.
     *
     * @throws ServerException
     *
     * @return mixed
     */
    protected function onErrorResponse(CommandInterface $command, ErrorResponseInterface $response)
    {
    }

    /**
     * Creates a new pipeline context and returns it, or returns the results of
     * a pipeline executed inside the optionally provided callable object.
     *
     * @param mixed ... Array of options, a callable for execution, or both.
     *
     * @return Pipeline|array
     */
    public function pipeline(/* arguments */)
    {
    }

    /**
     * Actual pipeline context initializer method.
     *
     * @param array $options  Options for the context.
     * @param mixed $callable Optional callable used to execute the context.
     *
     * @return Pipeline|array
     */
    protected function createPipeline(array $options = null, $callable = null)
    {
    }

    /**
     * Creates a new transaction context and returns it, or returns the results
     * of a transaction executed inside the optionally provided callable object.
     *
     * @param mixed ... Array of options, a callable for execution, or both.
     *
     * @return MultiExecTransaction|array
     */
    public function transaction(/* arguments */)
    {
    }

    /**
     * Actual transaction context initializer method.
     *
     * @param array $options  Options for the context.
     * @param mixed $callable Optional callable used to execute the context.
     *
     * @return MultiExecTransaction|array
     */
    protected function createTransaction(array $options = null, $callable = null)
    {
    }

    /**
     * Creates a new publish/subscribe context and returns it, or starts its loop
     * inside the optionally provided callable object.
     *
     * @param mixed ... Array of options, a callable for execution, or both.
     *
     * @return PubSubConsumer|null
     */
    public function pubSubLoop(/* arguments */)
    {
    }

    /**
     * Actual publish/subscribe context initializer method.
     *
     * @param array $options  Options for the context.
     * @param mixed $callable Optional callable used to execute the context.
     *
     * @return PubSubConsumer|null
     */
    protected function createPubSub(array $options = null, $callable = null)
    {
    }

    /**
     * Creates a new monitor consumer and returns it.
     *
     * @return MonitorConsumer
     */
    public function monitor()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
    }
}

