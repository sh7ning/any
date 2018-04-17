<?php

namespace Tree6bee\Any\Cache\Pipeline;

use Tree6bee\Any\Cache\Redis;

class Pipeline
{
    private $client;
    private $pipeline;

    private $responses = array();

    public function __construct(Redis $client)
    {
        $this->client = $client;
        $this->pipeline = new \SplQueue();
    }

    public function execute($callable = null)
    {
        $exception = null;

        try {
            if ($callable) {
                call_user_func($callable, $this);
            }

            $this->flushPipeline();
        } catch (\Exception $exception) {
            // NOOP
        }

        if ($exception) {
            throw $exception;
        }

        return $this->responses;
    }

    /**
     * Queues a command into the pipeline buffer.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return $this
     */
    public function __call($method, $arguments)
    {
        $command = $this->client->createCommand($method, $arguments);
        $this->recordCommand($command);

        return $this;
    }

    /**
     * Queues a command instance into the pipeline buffer.
     */
    protected function recordCommand($command)
    {
        $this->pipeline->enqueue($command);
    }

    /**
     * Flushes the buffer holding all of the commands queued so far.
     *
     * @return $this
     */
    protected function flushPipeline()
    {
        if ($this->pipeline->isEmpty()) {
            $this->pipeline = new \SplQueue();
        } else {
            $responses = $this->executePipeline($this->client, $this->pipeline);
            $this->responses = array_merge($this->responses, $responses);
        }

        return $this;
    }

    /**
     * Implements the logic to flush the queued commands and read the responses
     * from the current connection.
     *
     * @param Redis $connection
     * @param \SplQueue $commands
     *
     * @return array
     */
    protected function executePipeline(Redis $connection, \SplQueue $commands)
    {
        foreach ($commands as $command) {
            $connection->writeRequest($command);
        }

        $responses = array();

        while (! $commands->isEmpty()) {
            $commands->dequeue();
            $response = $connection->readResponse();

            $responses[] = $response;
        }

        return $responses;
    }
}
