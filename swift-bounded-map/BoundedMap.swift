public enum BoundedMapError: Error {
    case invalidLimit
}

/// Map with at most `limit` child tasks, retaining input order.
/// The transform must cooperate with cancellation; no tasks escape the group.
public func boundedMap<Input: Sendable, Output: Sendable>(
    _ inputs: [Input],
    limit: Int,
    transform: @escaping @Sendable (Input) async throws -> Output
) async throws -> [Output] {
    guard limit > 0 else { throw BoundedMapError.invalidLimit }
    try Task.checkCancellation()
    return try await withThrowingTaskGroup(of: (Int, Output).self) { group in
        var nextIndex = 0
        var results = Array<Output?>(repeating: nil, count: inputs.count)

        func enqueue(_ index: Int) {
            let input = inputs[index]
            group.addTask {
                try Task.checkCancellation()
                return (index, try await transform(input))
            }
        }

        while nextIndex < min(limit, inputs.count) {
            enqueue(nextIndex)
            nextIndex += 1
        }
        while let (index, output) = try await group.next() {
            try Task.checkCancellation()
            results[index] = .some(output)
            if nextIndex < inputs.count {
                enqueue(nextIndex)
                nextIndex += 1
            }
        }
        try Task.checkCancellation()
        // Successful group exhaustion means every scheduled input produced a slot.
        return results.map { $0! }
    }
}
