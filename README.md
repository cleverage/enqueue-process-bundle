CleverAge/ProcessEnqueueBundle Documentation
============================================

This bundle allows you to connect tasks from the process bundle to some consumers that launch processes.
This allows to multi-thread/parallelize processes.

## Important notice

Use events to notify consumers that something has happened and use commands when you want to wait for the result of an
action.

You need to statically map topics and commands to consumers in this bundle configuration else it will simply not work
without any notice.

## Quick example

````yaml
clever_age_process:
    configurations:
        # This process will dispatch events to the queue in a specific topic
        import.csv:
            tasks:
                # ...
                # Series of tasks that output an scalar or an array of scalar
                push_event:
                    service: '@CleverAge\EnqueueProcessBundle\Task\PushEventTask'
                    options:
                        topic: import_denormalize
        
        # This process will receive the output of the previous process through the queue
        import.denormalize:
            tasks:
                # This is just an example
                denormalize:
                    service: '@CleverAge\ProcessBundle\Task\Serialization\DenormalizerTask'
                    options:
                        class: Foo\Bar
                    outputs: [...] # Do stuff

clever_age_process_enqueue:
    topics:
        import_denormalize: # Map a topic to a process
            process: import.denormalize
````
