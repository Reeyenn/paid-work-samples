# Bound concurrent Swift work while preserving input order

An original AI-generated and locally tested tutorial sample by Reeyen Patel.
This is a command-line Swift example, not a shipped client application or a tested iOS UI.

`boundedMap` starts at most `limit` child tasks at once. As a result arrives, it records
that result at its original input index and starts the next input. Completion order
therefore does not determine output order. It does not create one waiting task for
every input at the start.

```swift
let lengths = try await boundedMap(["apple", "pear", "fig"], limit: 2) {
    $0.count
}
// [5, 4, 3]
```

The function uses Swift's structured throwing task group. A transform error propagates
out of the group, cancelling remaining children. The group waits for those children
to finish: cancellation is cooperative, and an uncooperative transform can delay
return. Parent cancellation is checked before scheduling and while collecting work.
There is no promise that arbitrary blocking code can be forcibly interrupted.

The concurrency bound covers direct child transforms, not extra work a transform
chooses to start elsewhere. Inputs and outputs must be `Sendable`; the transform is
`@Sendable`. An invalid nonpositive limit throws, including for empty input.

Results use an outer optional to distinguish a missing slot from a valid optional
output. Assigning `.some(output)` preserves an output that is itself `nil`.
The final unwrap is reached only after successful group exhaustion and cancellation
checks, when every scheduled input has returned a result.

## Run the checks

With a Swift compiler and matching macOS SDK:

```sh
swiftc -swift-version 6 -strict-concurrency=complete BoundedMap.swift Checks.swift -o checks
./checks
```

Locally verified on 10 September 2026 with Apple Swift 6.1.2 on arm64 macOS,
explicitly selecting the installed macOS 15.5 SDK:

```sh
swiftc -sdk /Library/Developer/CommandLineTools/SDKs/MacOSX15.5.sdk -swift-version 6 -strict-concurrency=complete BoundedMap.swift Checks.swift -o checks
./checks
```

The default local SDK was built with a newer compiler and failed to import Swift.
Selecting the compatible SDK for this command resolved that mismatch without
changing the machine's global developer configuration.

Checks cover a twelve-input workload, original output order, maximum active
transforms, optional `nil` results, empty input, invalid limit, a thrown transform
error and parent cancellation. They use local computations and short cancellable
sleeps, with no network requests. This is not a throughput benchmark.

Reference: [The Swift Programming Language: Concurrency](https://docs.swift.org/swift-book/documentation/the-swift-programming-language/concurrency/).
